<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Customer::class, 'customer');
    }

    public function index(): Response
    {
        return Inertia::render('Customers/Index', [
            'customers' => Customer::query()
                ->withCount('sales')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Customers/Form', [
            'customer' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        Customer::query()->create($validated);

        return redirect()->route('tenant.customers.index')->with('success', __('Customer created.'));
    }

    public function edit(Customer $customer): Response
    {
        $customer->loadCount('sales');

        return Inertia::render('Customers/Form', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $customer->update($validated);

        return redirect()->route('tenant.customers.index')->with('success', __('Customer updated.'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists() || (float) $customer->balance_due > 0.0001) {
            throw ValidationException::withMessages([
                'customer' => [__('customers.cannot_delete_has_sales')],
            ]);
        }

        $customer->delete();

        return redirect()->route('tenant.customers.index')->with('success', __('customers.removed'));
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
