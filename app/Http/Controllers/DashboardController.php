<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {
        $this->middleware('permission:dashboard.view');
    }

    public function index(Request $request): Response
    {
        $data = $this->dashboardService->getMetrics(
            $request->get('range', 'this_month'),
            $request->get('from'),
            $request->get('to')
        );

        return Inertia::render('Dashboard', $data);
    }
}
