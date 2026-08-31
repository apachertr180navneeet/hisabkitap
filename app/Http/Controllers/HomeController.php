<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ReconciliationService;
use App\Models\PsoConfig;
use App\Models\User;

class HomeController extends Controller
{
    protected $reconService;

    public function __construct(ReconciliationService $reconService)
    {
        $this->reconService = $reconService;
    }

    public function index()
    {
        $metrics = $this->reconService->getMetrics();
        $psoCount = PsoConfig::where('is_active', true)->count();
        $users = User::all();

        return view('landing', compact('metrics', 'psoCount', 'users'));
    }
}
