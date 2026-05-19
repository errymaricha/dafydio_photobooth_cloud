<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudPrintRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $printRequests = CloudPrintRequest::query()
            ->with(['customer:id,name,whatsapp_number', 'station:id,name,code'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $printRequests->items(),
            'meta' => [
                'current_page' => $printRequests->currentPage(),
                'last_page' => $printRequests->lastPage(),
                'total' => $printRequests->total(),
            ],
            'message' => null,
        ]);
    }
}
