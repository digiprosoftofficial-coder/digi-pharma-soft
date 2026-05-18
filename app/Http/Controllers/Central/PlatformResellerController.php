<?php

namespace App\Http\Controllers\Central;

use App\Domain\Platform\Models\Reseller;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class PlatformResellerController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', Reseller::class);

        $resellers = Reseller::query()
            ->withCount('tenants')
            ->orderBy('name')
            ->get();

        return Inertia::render('Platform/Resellers/Index', [
            'resellers' => $resellers,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Reseller::class);

        return Inertia::render('Platform/Resellers/Form', ['reseller' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Reseller::class);

        Reseller::query()->create($this->validated($request));

        return redirect()->route('platform.resellers.index')->with('success', __('platform.reseller_created'));
    }

    public function edit(Reseller $reseller): Response
    {
        $this->authorize('update', $reseller);

        return Inertia::render('Platform/Resellers/Form', ['reseller' => $reseller]);
    }

    public function update(Request $request, Reseller $reseller): RedirectResponse
    {
        $this->authorize('update', $reseller);

        $reseller->update($this->validated($request, $reseller));

        return redirect()->route('platform.resellers.index')->with('success', __('platform.reseller_updated'));
    }

    public function destroy(Reseller $reseller): RedirectResponse
    {
        $this->authorize('delete', $reseller);

        if ($reseller->tenants()->exists()) {
            return back()->withErrors(['reseller' => __('platform.reseller_has_tenants')]);
        }

        $reseller->delete();

        return redirect()->route('platform.resellers.index')->with('success', __('platform.reseller_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Reseller $reseller = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:64', 'alpha_dash',
                Rule::unique('resellers', 'slug')->ignore($reseller?->getKey()),
            ],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:64'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::lower($validated['slug']);
        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }
}
