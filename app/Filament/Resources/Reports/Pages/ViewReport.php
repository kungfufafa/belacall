<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\ReportHistory;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\Gate;

class ViewReport extends ViewRecord
{
    protected static string $resource = ReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignOperator')
                ->label('Assign Operator')
                ->visible(fn (): bool => $this->canAssign())
                ->form([
                    Select::make('assignee_id')
                        ->label('Operator')
                        ->options(fn (): array => User::query()
                            ->where('role', Role::OPERATOR)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Textarea::make('notes')
                        ->label('Catatan')
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    Gate::authorize('assign', $record);

                    $oldAssignee = $record->assignee?->name;
                    $record->update([
                        'assignee_id' => $data['assignee_id'],
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
                }),
            Action::make('followUp')
                ->label('Tindak Lanjut')
                ->visible(fn (): bool => $this->canFollowUp())
                ->form([
                    Select::make('status')
                        ->label('Status')
                        ->options(ReportStatus::class)
                        ->required(),
                    Select::make('category')
                        ->label('Kategori')
                        ->options(ReportCategory::class)
                        ->visible(fn (): bool => $this->canSetCategory())
                        ->required(fn (Get $get): bool => $this->canSetCategory()
                            && $this->requiresCategoryForStatus($get('status'))),
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

                    $updates = [
                        'status' => $data['status'],
                    ];

                    if ($this->canSetCategory() && ! empty($data['category'])) {
                        $updates['category'] = $data['category'];
                    }

                    $record->update($updates);

                    ReportHistory::create([
                        'report_id' => $record->id,
                        'user_id' => Filament::auth()->user()?->id,
                        'action' => 'STATUS_CHANGE',
                        'old_value' => $oldStatus,
                        'new_value' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                    ]);

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
            EditAction::make(),
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

    private function canSetCategory(): bool
    {
        $record = $this->getRecord();
        $status = $record->status instanceof ReportStatus
            ? $record->status->value
            : (string) $record->status;

        return in_array($status, [ReportStatus::SUBMITTED->value, ReportStatus::NEEDS_REVISION->value], true);
    }

    private function requiresCategoryForStatus(ReportStatus|string|null $status): bool
    {
        if (! $status) {
            return false;
        }

        $value = $status instanceof ReportStatus ? $status->value : (string) $status;

        return in_array($value, [
            ReportStatus::VERIFIED->value,
            ReportStatus::IN_PROGRESS->value,
            ReportStatus::RESOLVED->value,
            ReportStatus::CLOSED->value,
        ], true);
    }
}
