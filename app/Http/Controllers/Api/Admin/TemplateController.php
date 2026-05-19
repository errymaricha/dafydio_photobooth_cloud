<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = CloudTemplate::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $templates->items(),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'total' => $templates->total(),
            ],
            'message' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $template = CloudTemplate::query()->create($this->validatedData($request) + [
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return response()->json([
            'data' => $template,
            'meta' => [],
            'message' => 'Template created',
        ], 201);
    }

    public function update(Request $request, CloudTemplate $template): JsonResponse
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);

        $template->update($this->validatedData($request, true));

        return response()->json([
            'data' => $template,
            'meta' => [],
            'message' => 'Template updated',
        ]);
    }

    public function destroy(Request $request, CloudTemplate $template): JsonResponse
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);

        $template->delete();

        return response()->json([
            'data' => null,
            'meta' => [],
            'message' => 'Template deleted',
        ]);
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'access_level' => [$partial ? 'sometimes' : 'required', Rule::in(['marketplace', 'premium', 'private'])],
            'price_amount' => ['sometimes', 'numeric', 'min:0'],
            'price_currency' => ['sometimes', 'string', 'size:3'],
            'preview_path' => ['nullable', 'string', 'max:255'],
            'source_path' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
        ]);
    }
}
