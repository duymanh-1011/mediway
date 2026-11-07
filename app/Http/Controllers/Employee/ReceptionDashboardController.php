<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\EmployeeAccess;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ReceptionDashboardController extends Controller
{
    public function index(Request $request)
    {
        $employee = app(EmployeeAccess::class)->employee($request->user());
        try {
            $todayAppointments = Appointment::whereDate('appointment_date', today())->count();
            $bookedAppointments = Appointment::whereDate('appointment_date', today())
                ->whereHas('appointmentStatus', fn ($q) => $q->where('name', 'BOOKED'))->count();
            return view('employee.reception.dashboard', compact('employee', 'todayAppointments', 'bookedAppointments'));
        } catch (QueryException $exception) {
            logger()->error('Không thể tải tổng quan tiếp đón.', ['exception' => get_class($exception)]);
            return response()->view('employee.reception.unavailable', ['employee' => $employee, 'backRoute' => 'employee.reception.index'], 503);
        }
    }
}
