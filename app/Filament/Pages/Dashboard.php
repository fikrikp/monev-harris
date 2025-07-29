<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

use App\Filament\Widgets\AdminStats;
use App\Filament\Widgets\SupervisorStats;
use App\Filament\Widgets\ChiefStats;
use App\Filament\Widgets\BarangChartMeetingRoom;
use App\Filament\Widgets\QuickActionsWidget;

class Dashboard extends BaseDashboard
{

    public static function canAccess(): bool
    {
        // Dashboard ini hanya bisa diakses oleh non-staff (admin dan chief_engineering)
        return auth()->user()?->role !== 'staff';
    }
}
