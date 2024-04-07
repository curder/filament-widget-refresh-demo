<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $usersCount = User::query()->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_admin THEN 1 ELSE 0 END) AS admin,
            SUM(CASE WHEN is_active THEN 1 ELSE 0 END) AS active
        ')->first();

        return [
            Stat::make('Total', $usersCount->total)
                ->color('primary')
                ->description('Total users'),

            Stat::make('Admin', $usersCount->admin)
                ->color('danger')
                ->description('Admin users'),

            Stat::make('Active', $usersCount->active)
                ->color('success')
                ->description('Active users'),

        ];
    }
}
