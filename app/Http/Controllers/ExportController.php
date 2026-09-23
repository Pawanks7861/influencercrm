<?php

namespace App\Http\Controllers;

use App\Exports\CampaignsExport;
use App\Exports\InfluencersExport;
use App\Exports\PaymentsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:exports.manage|influencers.export|reports.view');
    }

    public function influencers(Request $request): BinaryFileResponse
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'influencers.'.($format === 'csv' ? 'csv' : 'xlsx');

        return Excel::download(new InfluencersExport($request), $filename);
    }

    public function campaigns(Request $request): BinaryFileResponse
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'campaigns.'.($format === 'csv' ? 'csv' : 'xlsx');

        return Excel::download(new CampaignsExport($request), $filename);
    }

    public function payments(Request $request): BinaryFileResponse
    {
        $format = $request->get('format', 'xlsx');
        $filename = 'payments.'.($format === 'csv' ? 'csv' : 'xlsx');

        return Excel::download(new PaymentsExport($request), $filename);
    }
}
