<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportSubmittedForTriage extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Report $report) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'ticket_number' => $this->report->ticket_number,
            'title' => $this->report->title,
            'status' => $this->report->status instanceof ReportStatus
                ? $this->report->status->value
                : (string) $this->report->status,
            'priority' => $this->report->priority?->value,
            'priority_label' => $this->report->priority?->label(),
            'location_name' => $this->report->location_name,
            'submitted_at' => $this->report->created_at?->toIso8601String(),
            'triage_target_role' => 'pimpinan',
        ];
    }
}
