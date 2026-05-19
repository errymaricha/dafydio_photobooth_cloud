<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sessions = CloudSession::query()
            ->with(['customer:id,name,whatsapp_number', 'station:id,name,code'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $sessions->items(),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page' => $sessions->lastPage(),
                'total' => $sessions->total(),
            ],
            'message' => null,
        ]);
    }
}
