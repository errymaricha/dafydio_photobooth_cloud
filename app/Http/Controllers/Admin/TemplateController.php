<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CloudTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $templates = CloudTemplate::query()
            ->where('tenant_id', $tenantId)
            ->latest()
            ->paginate(20)
            ->through(fn (CloudTemplate $template): array => $this->templatePayload($template));

        return Inertia::render('Admin/Templates/Index', [
            'templates' => $templates,
            'metrics' => [
                'total' => CloudTemplate::query()->where('tenant_id', $tenantId)->count(),
                'active' => CloudTemplate::query()->where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'marketplace' => CloudTemplate::query()->where('tenant_id', $tenantId)->where('access_level', 'marketplace')->count(),
                'premium' => CloudTemplate::query()->where('tenant_id', $tenantId)->where('access_level', 'premium')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CloudTemplate::query()->create($this->validatedData($request) + [
            'tenant_id' => $request->user()->tenant_id,
        ]);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, CloudTemplate $template): RedirectResponse
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);

        $template->update($this->validatedData($request, true));

        return back()->with('success', 'Template updated.');
    }

    public function destroy(Request $request, CloudTemplate $template): RedirectResponse
    {
        abort_unless($template->tenant_id === $request->user()->tenant_id, 404);

        $template->delete();

        return back()->with('success', 'Template deleted.');
    }

    private function validatedData(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $data = $request->validate([
            'name' => [$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'access_level' => [$partial ? 'sometimes' : 'required', Rule::in(['marketplace', 'premium', 'private'])],
            'price_amount' => ['sometimes', 'numeric', 'min:0'],
            'price_currency' => ['sometimes', 'string', 'size:3'],
            'preview_path' => ['nullable', 'string', 'max:255'],
            'source_path' => ['nullable', 'string', 'max:255'],
            'preview_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'source_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,json,zip', 'max:10240'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
        ]);

        unset($data['preview_file'], $data['source_file']);

        if ($request->hasFile('preview_file')) {
            $data['preview_path'] = $request->file('preview_file')->store('templates/previews', 'public');
        }

        if ($request->hasFile('source_file')) {
            $data['source_path'] = $request->file('source_file')->store('templates/sources', 'public');
        }

        return $data;
    }

    private function templatePayload(CloudTemplate $template): array
    {
        return [
            'id' => $template->id,
            'station_id' => $template->station_id,
            'station_template_id' => $template->station_template_id,
            'template_code' => $template->template_code,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'paper_size' => $template->paper_size,
            'access_level' => $template->access_level,
            'price_amount' => (float) $template->price_amount,
            'price_currency' => $template->price_currency,
            'preview_path' => $template->preview_path,
            'preview_url' => $this->previewUrl($template->preview_path),
            'source_path' => $template->source_path,
            'slots_count' => count($template->slots ?? []),
            'assets_count' => count($template->asset_manifest ?? []),
            'status' => $template->status,
            'created_at' => $template->created_at?->diffForHumans(),
        ];
    }

    private function previewUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
