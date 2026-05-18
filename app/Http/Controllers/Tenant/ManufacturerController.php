<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\Manufacturer;
use App\Domain\Catalog\Services\ManufacturerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreManufacturerRequest;
use App\Http\Requests\Catalog\UpdateManufacturerRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ManufacturerController extends Controller
{
    public function __construct(private readonly ManufacturerService $manufacturers)
    {
        $this->authorizeResource(Manufacturer::class, 'manufacturer');
    }

    public function index(): Response
    {
        return Inertia::render('Catalog/Manufacturers/Index', [
            'manufacturers' => Manufacturer::query()->withCount('products')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/Manufacturers/Form', ['manufacturer' => null]);
    }

    public function store(StoreManufacturerRequest $request): RedirectResponse
    {
        $this->manufacturers->create($request->validated());

        return redirect()->route('tenant.manufacturers.index')->with('success', __('Manufacturer created.'));
    }

    public function edit(Manufacturer $manufacturer): Response
    {
        return Inertia::render('Catalog/Manufacturers/Form', ['manufacturer' => $manufacturer]);
    }

    public function update(UpdateManufacturerRequest $request, Manufacturer $manufacturer): RedirectResponse
    {
        $this->manufacturers->update($manufacturer, $request->validated());

        return redirect()->route('tenant.manufacturers.index')->with('success', __('Manufacturer updated.'));
    }

    public function destroy(Manufacturer $manufacturer): RedirectResponse
    {
        $this->manufacturers->delete($manufacturer);

        return redirect()->route('tenant.manufacturers.index')->with('success', __('Manufacturer removed.'));
    }
}
