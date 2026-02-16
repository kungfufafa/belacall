<?php

namespace App\Observers;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\SlaConfig;
use App\Models\User;
use App\Notifications\ReportSubmittedForTriage;

class ReportObserver
{
    public function creating(Report $report): void
    {
        // Do not set deadlines on create - priority is NULL until pimpinan assigns operator
        // Deadlines will be set when priority is first assigned
    }

    public function created(Report $report): void
    {
        $status = $report->status instanceof ReportStatus
            ? $report->status
            : ReportStatus::tryFrom((string) $report->status);

        if ($status !== ReportStatus::SUBMITTED || $report->assignee_id !== null) {
            return;
        }

        User::query()
            ->where('role', Role::PIMPINAN)
            ->get()
            ->each(function (User $pimpinan) use ($report): void {
                $pimpinan->notify(new ReportSubmittedForTriage($report));
            });
    }

    public function updating(Report $report): void
    {
        // Only set deadlines when priority is being set for the first time
        if ($report->isDirty('priority') && $report->priority !== null) {
            $oldPriority = $report->getRawOriginal('priority');

            // Only calculate deadlines if this is the first assignment (old priority was NULL)
            if ($oldPriority === null) {
                $this->setDeadlines($report);
            }
            // If old priority was not NULL, don't recalculate
            // because priority should not be changed after first assignment
        }

        if ($report->isDirty('status')) {
            $this->trackStatusTimestamps($report);
        }
    }

    private function setDeadlines(Report $report): void
    {
        $priority = $report->priority;

        if (! $priority) {
            return;
        }

        $sla = SlaConfig::forPriority($priority);
        // Calculate deadlines from NOW (assignment time), not from created_at
        $from = now();
        $deadlines = $sla->computeDeadlines($from);

        $report->response_deadline = $deadlines['response_deadline'];
        $report->resolution_deadline = $deadlines['resolution_deadline'];
    }

    private function trackStatusTimestamps(Report $report): void
    {
        $newStatus = $this->normalizeStatus($report->status);
        $oldStatus = $this->normalizeStatus($report->getRawOriginal('status'));

        if (! $newStatus) {
            return;
        }

        $isFirstResponse = ! $report->responded_at
            && in_array($newStatus, [ReportStatus::IN_PROGRESS, ReportStatus::RESOLVED, ReportStatus::CLOSED], true)
            && $oldStatus !== $newStatus;

        if ($isFirstResponse) {
            $report->responded_at = now();
        }

        $isBeingResolved = $newStatus === ReportStatus::RESOLVED
            && $oldStatus !== ReportStatus::RESOLVED;

        if ($isBeingResolved) {
            $report->resolved_at = now();
        }

        $isReopenedFromResolved = $oldStatus === ReportStatus::RESOLVED
            && $newStatus !== ReportStatus::RESOLVED;

        if ($isReopenedFromResolved) {
            $report->resolved_at = null;
        }
    }

    private function normalizeStatus(mixed $status): ?ReportStatus
    {
        if ($status instanceof ReportStatus) {
            return $status;
        }

        if (! is_string($status) || $status === '') {
            return null;
        }

        return ReportStatus::tryFrom($status);
    }
}
