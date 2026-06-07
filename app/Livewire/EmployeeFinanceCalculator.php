<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Finance;
use App\Models\Semester;

class EmployeeFinanceCalculator extends Component
{
    public $employeeId;
    public $semesterId;
    public $semesters;
    
    public $hours = 0;
    public $hourlyRate = 0;
    public $totalDue = 0;
    
    // Existing fields
    public $salary = 0;
    public $bonus = 0;
    public $deductions = 0;

    public function mount($employeeId)
    {
        $this->employeeId = $employeeId;
        $this->semesters = Semester::all();
        
        $currentSemester = $this->semesters->where('is_current', true)->first() 
            ?? $this->semesters->first();
            
        if ($currentSemester) {
            $this->semesterId = $currentSemester->id;
            $this->loadFinanceData();
        }
    }

    public function updatedSemesterId()
    {
        $this->loadFinanceData();
    }

    public function loadFinanceData()
    {
        if (!$this->semesterId) return;

        $finance = Finance::where('employee_id', $this->employeeId)
            ->where('semester_id', $this->semesterId)
            ->first();

        if ($finance) {
            $this->hours = $finance->hours;
            $this->hourlyRate = $finance->hourly_rate;
            $this->totalDue = $finance->total_due;
            $this->salary = $finance->salary;
            $this->bonus = $finance->bonus;
            $this->deductions = $finance->deductions;
        } else {
            $this->hours = 0;
            $this->hourlyRate = 0;
            $this->totalDue = 0;
            $this->salary = 0;
            $this->bonus = 0;
            $this->deductions = 0;
        }
    }

    public function updatedHours()
    {
        $this->calculateTotal();
    }

    public function updatedHourlyRate()
    {
        $this->calculateTotal();
    }

    public function calculateTotal()
    {
        $h = floatval($this->hours ?: 0);
        $r = floatval($this->hourlyRate ?: 0);
        $this->totalDue = $h * $r;
    }

    public function save()
    {
        $this->validate([
            'semesterId' => 'required',
            'hours' => 'numeric|min:0',
            'hourlyRate' => 'numeric|min:0',
            'salary' => 'numeric|min:0',
            'bonus' => 'numeric|min:0',
            'deductions' => 'numeric|min:0',
        ]);

        Finance::updateOrCreate(
            [
                'employee_id' => $this->employeeId,
                'semester_id' => $this->semesterId,
            ],
            [
                'hours' => $this->hours,
                'hourly_rate' => $this->hourlyRate,
                'total_due' => $this->totalDue,
                'salary' => $this->salary,
                'bonus' => $this->bonus,
                'deductions' => $this->deductions,
            ]
        );

        session()->flash('message', 'Financial data saved successfully for this semester.');
    }

    public function render()
    {
        $employee = \App\Models\User::find($this->employeeId);
        $selectedSemester = $this->semesterId ? \App\Models\Semester::find($this->semesterId) : null;

        return view('livewire.employee-finance-calculator', [
            'employee' => $employee,
            'selectedSemester' => $selectedSemester
        ]);
    }
}
