<?php

namespace App\Console\Commands;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\User;
use App\Notifications\SlaBreachWarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckSlaBreachCommand extends Command
{
    protected $signature = 'reports:check-sla';

    protected $description = 'Check for SLA breaches and send warnings to operators and pimpinan';

    public function handle(): int
    {
        $breachedReports = Report::query()
            ->with(['assignee', 'user'])
            ->whereNotIn('status', [
                ReportStatus::CLOSED,
                ReportStatus::REJECTED,
            ])
            ->where(function ($query): void {
                $query->where(function ($q): void {
                    $q->whereNotNull('response_deadline')
                        ->where('response_deadline', '<', now())
                        ->whereNull('responded_at');
                })->orWhere(function ($q): void {
                    $q->whereNotNull('resolution_deadline')
                        ->where('resolution_deadline', '<', now())
                        ->whereNotIn('status', [ReportStatus::RESOLVED, ReportStatus::CLOSED]);
                });
            })
            ->get();

        if ($breachedReports->isEmpty()) {
            $this->info('No SLA breaches found.');

            return self::SUCCESS;
        }

        $pimpinanUsers = User::query()->where('role', Role::PIMPINAN)->get();
        $adminUsers = User::query()->where('role', Role::ADMIN)->get();
        $recipients = $pimpinanUsers->merge($adminUsers);

        foreach ($breachedReports as $report) {
            $notifiables = $recipients->collect();

            if ($report->assignee) {
                $notifiables->push($report->assignee);
            }

            $breachType = $this->determineBreachType($report);

            Notification::send(
                $notifiables->unique('id'),
                new SlaBreachWarning($report, $breachType)
            );
        }

        $this->info("Sent SLA breach warnings for {$breachedReports->count()} report(s).");

        return self::SUCCESS;
    }

    private function determineBreachType(Report $report): string
    {
        $responseBreached = $report->response_deadline
            && $report->response_deadline->isPast()
            && ! $report->responded_at;

        $resolutionBreached = $report->resolution_deadline
            && $report->resolution_deadline->isPast()
            && ! in_array($report->status, [ReportStatus::RESOLVED, ReportStatus::CLOSED]);

        if ($responseBreached && $resolutionBreached) {
            return 'both';
        }

        return $responseBreached ? 'response' : 'resolution';
    }
}
