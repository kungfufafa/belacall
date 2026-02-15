<?php

namespace App\Observers;

use App\Enums\ReportStatus;
use App\Models\Report;
use App\Models\SlaConfig;

class ReportObserver
{
    public function creating(Report $report): void
    {
        $this->setDeadlines($report);
    }

    public function updating(Report $report): void
    {
        if ($report->isDirty('priority')) {
            $this->setDeadlines($report);
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
        $from = $report->created_at ?? now();
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
            && $oldStatus === ReportStatus::SUBMITTED
            && $newStatus !== ReportStatus::SUBMITTED;

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
