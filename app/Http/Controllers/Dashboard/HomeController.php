<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\Currency;
use App\Models\Executive;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(){
        $year = Carbon::now()->year;

        return view('dashboard.index',compact('year'));
    }

    public function import()
    {
        return view('dashboard.import');
    }

}

