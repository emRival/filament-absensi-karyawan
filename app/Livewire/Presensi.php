<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Presensi extends Component
{
    public $latitude;
    public $longitude;
    public $insideRadius = false;
    public $finish = false;

    public function mount()
    {
        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        if (!$schedule) {
            return redirect('/admin/schedules');
        }
    }


    public function render()
    {

        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        $attendance = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', date('Y-m-d'))
            ->first();

        if ($attendance) {
            if ($attendance->start_time  && $attendance->end_time) {
                $this->finish = true;
            }
        }




        return view('livewire.presensi', [
            'schedule' => $schedule,
            'insideRadius' => $this->insideRadius,
            'attendance' => $attendance,
            'finish' => $this->finish,
        ]);
    }

    public function store()
    {
        $this->validate([
            'latitude' => 'required',
            'longitude' => 'required',
        ]);

        $schedule = Schedule::where('user_id', Auth::user()->id)->first();
        if ($schedule) {
            $attendance = Attendance::where('user_id', Auth::user()->id)->whereDate('created_at', date('Y-m-d'))
                ->first();
            if (!$attendance) {

                $terlambat = Carbon::now()->lt(Carbon::parse($schedule->shift->start_time))
                    ? '0'
                    : (int) abs(Carbon::now()->diffInMinutes(Carbon::parse($schedule->shift->start_time), false));


                // dd($terlambat);

                $attendance = Attendance::create([
                    'user_id' => Auth::user()->id,
                    'schedule_latitude' => $schedule->office->latitude,
                    'schedule_longitude' => $schedule->office->longitude,
                    'schedule_start_time' => $schedule->shift->start_time,
                    'schedule_end_time' => $schedule->shift->end_time,
                    'start_latitude' => $this->latitude,
                    'start_longitude' => $this->longitude,
                    'start_time' => Carbon::now()->toTimeString(),
                    'status'    => 'Hadir',
                    'keterlambatan' => $terlambat,

                ]);
            } else {
                $attendance->update([
                    'end_latitude' => $this->latitude,
                    'end_longitude' => $this->longitude,
                    'end_time' => Carbon::now()->toTimeString(),
                ]);
            }

            return redirect()->route('presensi')->with('message', 'Attendance recorded successfully.');
        }

        session()->flash('error', 'Schedule not found.');
    }
}
