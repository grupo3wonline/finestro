<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CalculatorController extends Controller
{
    public function index(): View
    {
        return view('calculators.index');
    }

    public function investment(): View
    {
        return view('calculators.investment');
    }

    public function interest(): View
    {
        return view('calculators.interest');
    }

    public function retirement(): View
    {
        return view('calculators.retirement');
    }

    public function thirteenthSalary(): View
    {
        return view('calculators.thirteenth-salary');
    }

    public function vacation(): View
    {
        return view('calculators.vacation');
    }

    public function financing(): View
    {
        return view('calculators.financing');
    }

    /**
     * Currency exchange rates and converter.
     */
    public function currency(): View
    {
        return view('calculators.currency');
    }
}
