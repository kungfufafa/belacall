<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SlaBreachWarning extends Notification
{
    use Queueable;

    public function __construct(public Report $report, public string $breachType = 'resolution') {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $priority = $this->report->priority;

        return [
            'report_id' => $this->report->id,
            'ticket_number' => $this->report->ticket_number,
            'title' => $this->report->title,
            'priority' => $priority?->value,
            'priority_label' => $priority?->label(),
            'breach_type' => $this->breachType,
            'breach_label' => $this->breachLabel(),
            'response_deadline' => $this->report->response_deadline?->toIso8601String(),
            'resolution_deadline' => $this->report->resolution_deadline?->toIso8601String(),
            'assignee_name' => $this->report->assignee?->name,
        ];
    }

    private function breachLabel(): string
    {
        return match ($this->breachType) {
            'response' => 'Waktu respon terlewat',
            'resolution' => 'Waktu penyelesaian terlewat',
            'both' => 'Waktu respon & penyelesaian terlewat',
            default => 'SLA terlewat',
        };
    }
}
