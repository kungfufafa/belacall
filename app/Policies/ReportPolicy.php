<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canMonitorReports($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Report $report): bool
    {
        if ($user->role === Role::OPERATOR) {
            return $report->assignee_id === $user->id;
        }

        return $this->canMonitorReports($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function assign(User $user, Report $report): bool
    {
        $status = $report->status instanceof ReportStatus
            ? $report->status->value
            : (string) $report->status;

        if (in_array($status, [
            ReportStatus::RESOLVED->value,
            ReportStatus::CLOSED->value,
            ReportStatus::REJECTED->value,
        ], true)) {
            return false;
        }

        return in_array($user->role, [Role::ADMIN, Role::PIMPINAN], true);
    }

    public function followUp(User $user, Report $report): bool
    {
        $status = $report->status instanceof ReportStatus
            ? $report->status
            : ReportStatus::tryFrom((string) $report->status);

        if ($status?->isFinal()) {
            return $this->isAdmin($user);
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->role === Role::OPERATOR) {
            return $report->assignee_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Report $report): bool
    {
        return $this->isAdmin($user);
    }

    private function canMonitorReports(User $user): bool
    {
        return in_array($user->role, [Role::ADMIN, Role::OPERATOR, Role::PIMPINAN], true);
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === Role::ADMIN;
    }
}
