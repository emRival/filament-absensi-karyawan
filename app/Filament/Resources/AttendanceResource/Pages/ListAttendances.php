<?php

namespace App\Filament\Resources\AttendanceResource\Pages;

use App\Filament\Resources\AttendanceResource;
use App\Models\Schedule;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListAttendances extends ListRecords
{
    protected static string $resource = AttendanceResource::class;

    protected function getHeaderActions(): array
    {
        $schedule = Schedule::where('user_id', Auth::id())->first();

        return [
            Action::make('presensi')
                ->url('presensi')
                ->color('success')
                ->hidden(!$schedule),
            Actions\CreateAction::make(),
        ];
    }
}
