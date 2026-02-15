<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\ReportHistory;
use App\Models\User;
use App\Notifications\ReportAssigned;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_number')
                    ->label('Nomor Tiket')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(100)
                    ->toggleable(),
                TextColumn::make('priority')
                    ->label('Prioritas')
                    ->badge()
                    ->color(fn (ReportPriority $state): string => $state->color())
                    ->formatStateUsing(fn (ReportPriority $state): string => $state->label()),
                TextColumn::make('location_name')
                    ->label('Lokasi')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Pelapor')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('assignee.name')
                    ->label('Petugas')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (ReportStatus $state): string => $state->color()),
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReportStatus::class),
                SelectFilter::make('priority')
                    ->label('Prioritas')
                    ->options(ReportPriority::class),
            ])
            ->checkIfRecordIsSelectableUsing(fn (Report $record): bool => Gate::allows('assign', $record))
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('assignOperator')
                        ->label('Assign Operator')
                        ->icon(Heroicon::OutlinedUserPlus)
                        ->visible(fn (): bool => ReportResource::canBulkAssign())
                        ->form([
                            Select::make('assignee_id')
                                ->label('Operator')
                                ->options(fn (): array => ReportResource::operatorOptionsWithLoad())
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('priority')
                                ->label('Prioritas')
                                ->options(ReportPriority::class)
                                ->required(),
                            Textarea::make('notes')
                                ->label('Catatan')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $actor = Filament::auth()->user();
                            $assignee = User::query()->find($data['assignee_id']);

                            if (! $assignee || ! $actor) {
                                return;
                            }

                            $records
                                ->filter(fn (Report $record): bool => Gate::forUser($actor)->allows('assign', $record))
                                ->each(function (Report $record) use ($assignee, $actor, $data): void {
                                    $oldAssignee = $record->assignee?->name;
                                    $oldPriority = $record->priority instanceof ReportPriority
                                        ? $record->priority->value
                                        : (string) $record->priority;
                                    $oldStatus = $record->status instanceof ReportStatus
                                        ? $record->status->value
                                        : (string) $record->status;
                                    $status = $record->status instanceof ReportStatus
                                        ? $record->status
                                        : ReportStatus::tryFrom((string) $record->status);

                                    $updates = [
                                        'assignee_id' => $assignee->id,
                                        'priority' => $data['priority'],
                                    ];

                                    if ($status && in_array($status, [ReportStatus::SUBMITTED, ReportStatus::NEEDS_REVISION], true)) {
                                        $updates['status'] = ReportStatus::VERIFIED->value;
                                    }

                                    $record->update($updates);
                                    $record->refresh();

                                    ReportHistory::create([
                                        'report_id' => $record->id,
                                        'user_id' => $actor->id,
                                        'action' => 'ASSIGNMENT',
                                        'old_value' => $oldAssignee,
                                        'new_value' => $assignee->name,
                                        'notes' => $data['notes'] ?? null,
                                    ]);

                                    if ($oldPriority !== $data['priority']) {
                                        ReportHistory::create([
                                            'report_id' => $record->id,
                                            'user_id' => $actor->id,
                                            'action' => 'PRIORITY_CHANGE',
                                            'old_value' => $oldPriority,
                                            'new_value' => $data['priority'],
                                            'notes' => 'Prioritas ditetapkan saat penugasan massal.',
                                        ]);
                                    }

                                    if (($updates['status'] ?? null) === ReportStatus::VERIFIED->value && $oldStatus !== ReportStatus::VERIFIED->value) {
                                        ReportHistory::create([
                                            'report_id' => $record->id,
                                            'user_id' => $actor->id,
                                            'action' => 'STATUS_CHANGE',
                                            'old_value' => $oldStatus,
                                            'new_value' => ReportStatus::VERIFIED->value,
                                            'notes' => 'Laporan diverifikasi saat penugasan oleh pimpinan.',
                                        ]);
                                    }

                                    $assignee->notify(new ReportAssigned($record, $actor));
                                });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'assignee']));
    }
}
