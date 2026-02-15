<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\ReportHistory;
use App\Notifications\ReportAssigned;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignOperator')
                ->label('Assign Operator')
                ->color('info')
                ->visible(fn (): bool => $this->canAssign())
                ->form([
                    Select::make('assignee_id')
                        ->label('Operator')
                        ->options(fn (): array => ReportResource::operatorOptionsWithLoad())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('priority')
                        ->label('Prioritas')
                        ->options(fn (): array => ReportResource::priorityOptionsWithSla())
                        ->required()
                        ->disabled(fn (): bool => $this->getRecord()->priority !== null)
                        ->dehydrated()
                        ->helperText(fn (): ?string => $this->getRecord()->priority !== null
                            ? 'Prioritas sudah ditetapkan dan tidak dapat diubah.'
                            : 'Prioritas hanya dapat ditetapkan sekali saat penugasan pertama. Label prioritas menampilkan target respon dan target selesai (eskalasi SLA).'),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    Gate::authorize('assign', $record);

                    $oldAssignee = $record->assignee?->name;
                    $oldPriority = $record->priority instanceof ReportPriority
                        ? $record->priority->value
                        : ($record->priority !== null ? (string) $record->priority : null);
                    $oldStatus = $record->status instanceof ReportStatus
                        ? $record->status->value
                        : (string) $record->status;
                    $status = $record->status instanceof ReportStatus
                        ? $record->status
                        : ReportStatus::tryFrom((string) $record->status);

                    $updates = [
                        'assignee_id' => $data['assignee_id'],
                    ];

                    // Only update priority if it was not already set
                    if ($record->priority === null) {
                        $updates['priority'] = $data['priority'];
                    }

                    if ($status === ReportStatus::SUBMITTED) {
                        $updates['status'] = ReportStatus::VERIFIED->value;
                    }

                    $record->update([
                        ...$updates,
                    ]);
                    $record->refresh();

                    ReportHistory::create([
                        'report_id' => $record->id,
                        'user_id' => Filament::auth()->user()?->id,
                        'action' => 'ASSIGNMENT',
                        'old_value' => $oldAssignee,
                        'new_value' => $record->assignee?->name,
                        'notes' => $data['notes'] ?? null,
                    ]);

                    if ($oldPriority !== ($updates['priority'] ?? $oldPriority)) {
                        ReportHistory::create([
                            'report_id' => $record->id,
                            'user_id' => Filament::auth()->user()?->id,
                            'action' => 'PRIORITY_CHANGE',
                            'old_value' => $oldPriority,
                            'new_value' => $data['priority'],
                            'notes' => 'Prioritas ditetapkan saat penugasan.',
                        ]);
                    }

                    if (($updates['status'] ?? null) === ReportStatus::VERIFIED->value && $oldStatus !== ReportStatus::VERIFIED->value) {
                        ReportHistory::create([
                            'report_id' => $record->id,
                            'user_id' => Filament::auth()->user()?->id,
                            'action' => 'STATUS_CHANGE',
                            'old_value' => $oldStatus,
                            'new_value' => ReportStatus::VERIFIED->value,
                            'notes' => 'Laporan diverifikasi saat penugasan oleh pimpinan.',
                        ]);
                    }

                    if ($record->assignee) {
                        $record->assignee->notify(new ReportAssigned($record, Filament::auth()->user()));
                    }
                }),
            Action::make('followUp')
                ->label('Tindak Lanjut')
                ->color('secondary')
                ->visible(fn (): bool => $this->canFollowUp() && $this->hasFollowUpStatusOptions())
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options(fn (): array => $this->availableStatusOptions())
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3),
                    FileUpload::make('evidence')
                        ->label('Bukti Foto (Opsional)')
                        ->disk('public')
                        ->directory('evidences')
                        ->visibility('public')
                        ->image()
                        ->maxSize(5120),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    Gate::authorize('followUp', $record);

                    $oldStatus = $record->status instanceof ReportStatus
                        ? $record->status->value
                        : (string) $record->status;
                    $oldStatusEnum = $record->status instanceof ReportStatus
                        ? $record->status
                        : ReportStatus::tryFrom((string) $record->status);
                    $newStatusEnum = ReportStatus::tryFrom((string) $data['status']);

                    if (! $oldStatusEnum || ! $newStatusEnum || ! $oldStatusEnum->canTransitionTo($newStatusEnum)) {
                        throw ValidationException::withMessages([
                            'status' => 'Transisi status tidak valid untuk tahap laporan saat ini.',
                        ]);
                    }

                    if (! $this->canActorSetStatus($oldStatusEnum, $newStatusEnum)) {
                        throw ValidationException::withMessages([
                            'status' => 'Status ini bukan kewenangan Anda.',
                        ]);
                    }

                    $updates = [
                        'status' => $data['status'],
                    ];

                    $record->update($updates);

                    if ($oldStatus !== $data['status'] || ! empty($data['notes'])) {
                        ReportHistory::create([
                            'report_id' => $record->id,
                            'user_id' => Filament::auth()->user()?->id,
                            'action' => 'STATUS_CHANGE',
                            'old_value' => $oldStatus,
                            'new_value' => $data['status'],
                            'notes' => $data['notes'] ?? null,
                        ]);
                    }

                    if (! empty($data['evidence'])) {
                        $record->evidences()->create([
                            'file_path' => $data['evidence'],
                            'file_type' => 'image',
                            'uploaded_by' => Filament::auth()->user()?->name,
                        ]);

                        ReportHistory::create([
                            'report_id' => $record->id,
                            'user_id' => Filament::auth()->user()?->id,
                            'action' => 'EVIDENCE_UPLOAD',
                            'new_value' => basename($data['evidence']),
                        ]);
                    }
                }),
            EditAction::make()
                ->color('warning'),
        ];
    }

    private function canAssign(): bool
    {
        $record = $this->getRecord();

        return Gate::allows('assign', $record);
    }

    private function canFollowUp(): bool
    {
        $record = $this->getRecord();

        return Gate::allows('followUp', $record);
    }

    private function hasFollowUpStatusOptions(): bool
    {
        return $this->availableStatusOptions() !== [];
    }

    private function isOperator(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::OPERATOR;
    }

    private function isPimpinan(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::PIMPINAN;
    }

    /**
     * @return array<string, string>
     */
    private function availableStatusOptions(): array
    {
        $currentStatus = $this->getRecord()->status instanceof ReportStatus
            ? $this->getRecord()->status
            : ReportStatus::tryFrom((string) $this->getRecord()->status);

        if (! $currentStatus) {
            return [];
        }

        return collect(ReportStatus::cases())
            ->filter(fn (ReportStatus $status): bool => $currentStatus->canTransitionTo($status))
            ->filter(fn (ReportStatus $status): bool => $this->canActorSetStatus($currentStatus, $status))
            ->mapWithKeys(fn (ReportStatus $status): array => [$status->value => $status->label()])
            ->all();
    }

    private function canActorSetStatus(ReportStatus $from, ReportStatus $to): bool
    {
        if ($this->isPimpinan()) {
            return in_array($to, [ReportStatus::NEEDS_REVISION, ReportStatus::REJECTED], true);
        }

        if (! $this->isOperator()) {
            return true;
        }

        return match ($from) {
            ReportStatus::VERIFIED => in_array($to, [ReportStatus::IN_PROGRESS], true),
            ReportStatus::IN_PROGRESS => in_array($to, [ReportStatus::RESOLVED], true),
            ReportStatus::RESOLVED => in_array($to, [ReportStatus::CLOSED, ReportStatus::IN_PROGRESS], true),
            default => false,
        };
    }
}
