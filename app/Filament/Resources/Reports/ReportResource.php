<?php

namespace App\Filament\Resources\Reports;

use App\Enums\ReportPriority;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\Schemas\ReportForm;
use App\Filament\Resources\Reports\Schemas\ReportInfolist;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Models\Report;
use App\Models\SlaConfig;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return ReportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReportInfolist::configure($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();

        if ($user?->role === Role::ADMIN) {
            $query->where(function (Builder $builder): void {
                $builder
                    ->where('status', '!=', ReportStatus::SUBMITTED->value)
                    ->orWhereNotNull('assignee_id');
            });
        }

        if ($user?->role === Role::OPERATOR) {
            $query->where('assignee_id', $user->id);
        }

        return $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['user', 'assignee', 'evidences', 'histories.user']);
    }

    public static function table(Table $table): Table
    {
        return ReportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function operatorOptionsWithLoad(): array
    {
        return User::query()
            ->where('role', Role::OPERATOR)
            ->withCount([
                'reportsAssigned as in_progress_count' => fn ($query) => $query
                    ->where('status', ReportStatus::IN_PROGRESS->value),
            ])
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (User $user): array {
                $count = (int) ($user->in_progress_count ?? 0);
                $label = sprintf('%s (%d tugas)', $user->name, $count);

                return [$user->id => $label];
            })
            ->all();
    }

    public static function canBulkAssign(): bool
    {
        $user = Filament::auth()->user();

        return $user?->role === Role::PIMPINAN;
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptionsWithSla(): array
    {
        return collect(ReportPriority::cases())
            ->mapWithKeys(function (ReportPriority $priority): array {
                $sla = SlaConfig::forPriority($priority);
                $responseTarget = self::formatDuration((int) $sla->response_time_minutes);
                $resolutionTarget = self::formatDuration((int) $sla->resolution_time_minutes);
                $label = sprintf(
                    '%s (Respon %s | Selesai %s)',
                    $priority->label(),
                    $responseTarget,
                    $resolutionTarget
                );

                return [$priority->value => $label];
            })
            ->all();
    }

    private static function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '0 menit';
        }

        $days = intdiv($minutes, 1440);
        $remainingMinutes = $minutes % 1440;
        $hours = intdiv($remainingMinutes, 60);
        $mins = $remainingMinutes % 60;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} hari";
        }

        if ($hours > 0) {
            $parts[] = "{$hours} jam";
        }

        if ($mins > 0) {
            $parts[] = "{$mins} menit";
        }

        return implode(' ', $parts);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\Reports\Pages\ListReports::route('/'),
            'create' => \App\Filament\Resources\Reports\Pages\CreateReport::route('/create'),
            'view' => \App\Filament\Resources\Reports\Pages\ViewReport::route('/{record}'),
            'edit' => \App\Filament\Resources\Reports\Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
