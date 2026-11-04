<?php

namespace App\\View\\Composers;

use Illuminate\\Support\\Collection;
use Illuminate\\Support\\Facades\\Auth;
use Illuminate\\View\\View;

final class AppSidebarComposer
{
    public function compose(View $view): void
    {
        $user = Auth::user();
        $employee = $user?->employee;

        $unseenRequestDecisions = $employee
            ? $employee->leaveRequests()
                ->whereIn('status', ['approved', 'rejected'])
                ->whereNull('decision_seen_at')
                ->count()
                + $employee->overtimeRequests()
                    ->whereIn('approval_status', ['approved', 'rejected'])
                    ->whereNull('decision_seen_at')
                    ->count()
                + $employee->attendanceCorrections()
                    ->whereIn('status', ['approved', 'rejected'])
                    ->whereNull('decision_seen_at')
                    ->count()
            : 0;

        $view->with([
            'sidebarUser' => $user,
            'sidebarEmployee' => $employee,
            'isManager' => $user?->isManager() ?? false,
            'isEmployee' => $user?->isEmployee() ?? false,
            'unreadSystemNotifications' => $user?->unreadNotifications()->count() ?? 0,
            'latestSystemNotifications' => $user
                ? $user->notifications()->latest()->limit(5)->get()
                : new Collection(),
            'unseenRequestDecisions' => $unseenRequestDecisions,
        ]);
    }
}
