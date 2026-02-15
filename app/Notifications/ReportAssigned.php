<?php

namespace App\Notifications;

use App\Models\Report;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportAssigned extends Notification
{
    use Queueable;

    public function __construct(public Report $report, public ?User $assignedBy = null) {}

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
        $priority = $this->report->priority;

        return [
            'report_id' => $this->report->id,
            'ticket_number' => $this->report->ticket_number,
            'title' => $this->report->title,
            'status' => $this->report->status?->value ?? (string) $this->report->status,
            'priority' => $priority?->value,
            'priority_label' => $priority?->label(),
            'response_deadline' => $this->report->response_deadline?->toIso8601String(),
            'resolution_deadline' => $this->report->resolution_deadline?->toIso8601String(),
            'assigned_by_id' => $this->assignedBy?->id,
            'assigned_by_name' => $this->assignedBy?->name,
        ];
    }
}
