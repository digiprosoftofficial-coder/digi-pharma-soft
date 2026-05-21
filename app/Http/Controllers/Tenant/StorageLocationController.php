<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Catalog\Models\StorageLocation;
use App\Domain\Catalog\Services\StorageLocationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\StoreStorageLocationRequest;
use App\Http\Requests\Catalog\UpdateStorageLocationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class StorageLocationController extends Controller
{
    public function __construct(private readonly StorageLocationService $locations)
    {
        $this->authorizeResource(StorageLocation::class, 'storage_location');
    }

    public function index(): Response
    {
        return Inertia::render('Catalog/StorageLocations/Index', [
            'locations' => StorageLocation::query()
                ->withCount(['products', 'batches'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Catalog/StorageLocations/Form', ['location' => null]);
    }

    public function store(StoreStorageLocationRequest $request): RedirectResponse
    {
        $this->locations->create($request->validated());

        return redirect()
            ->route('tenant.storage-locations.index')
            ->with('success', __('catalog.storage_location_created'));
    }

    public function edit(StorageLocation $storageLocation): Response
    {
        return Inertia::render('Catalog/StorageLocations/Form', [
            'location' => $storageLocation,
        ]);
    }

    public function update(UpdateStorageLocationRequest $request, StorageLocation $storageLocation): RedirectResponse
    {
        $this->locations->update($storageLocation, $request->validated());

        return redirect()
            ->route('tenant.storage-locations.index')
            ->with('success', __('catalog.storage_location_updated'));
    }

    public function destroy(StorageLocation $storageLocation): RedirectResponse
    {
        $this->locations->delete($storageLocation);

        return redirect()
            ->route('tenant.storage-locations.index')
            ->with('success', __('catalog.storage_location_deleted'));
    }
}
