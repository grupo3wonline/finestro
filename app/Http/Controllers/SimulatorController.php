<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    public function index(): View
    {
        if (!auth()->user()->hasFeature('simulators')) {
            return view('simulators.locked');
        }

        return view('simulators.index');
    }

    public function investment(): View
    {
        if (!auth()->user()->hasFeature('simulators')) {
            return view('simulators.locked');
        }

        return view('simulators.investment');
    }

    public function retirement(): View
    {
        if (!auth()->user()->hasFeature('simulators')) {
            return view('simulators.locked');
        }

        return view('simulators.retirement');
    }

    public function budget(): View
    {
        if (!auth()->user()->hasFeature('simulators')) {
            return view('simulators.locked');
        }

        return view('simulators.budget');
    }
}
