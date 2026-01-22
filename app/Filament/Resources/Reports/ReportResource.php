<?php

namespace App\Filament\Resources\Reports;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Filament\Resources\Reports\Schemas\ReportForm;
use App\Filament\Resources\Reports\Schemas\ReportInfolist;
use App\Filament\Resources\Reports\Tables\ReportsTable;
use App\Models\Report;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

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

        return $user && in_array($user->role, [Role::ADMIN, Role::PIMPINAN], true);
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
