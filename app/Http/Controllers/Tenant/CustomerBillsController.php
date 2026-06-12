<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Sales\Models\Customer;
use App\Domain\Sales\Models\Sale;
use App\Domain\Sales\Models\SalePayment;
use App\Http\Controllers\Controller;
use App\Support\Payments\PaymentMethods;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CustomerBillsController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);

        $customers = Customer::query()
            ->whereHas('sales', fn ($q) => $q->where('status', 'posted')->where('due', '>', 0))
            ->withSum(
                ['sales as open_due_sum' => fn ($q) => $q->where('status', 'posted')->where('due', '>', 0)],
                'due',
            )
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Sales/CustomerBills', [
            'customers' => $customers,
        ]);
    }

    public function show(Request $request, Customer $customer): Response
    {
        abort_unless(auth()->user()?->can('customers.view'), 403);
        abort_unless((int) auth()->user()?->tenant_id === (int) $customer->tenant_id, 403);

        $openSales = Sale::query()
            ->where('customer_id', $customer->getKey())
            ->where('due', '>', 0)
            ->where('status', 'posted')
            ->orderBy('sold_at')
            ->get(['id', 'invoice_no', 'sold_at', 'total', 'rounded_total', 'paid', 'due']);

        $openDue = (float) $openSales->sum('due');

        $paymentHistory = SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->where('customer_id', $customer->getKey()))
            ->with('sale:id,invoice_no,customer_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return Inertia::render('Sales/CustomerBillShow', [
            'customer' => [
                'id' => $customer->getKey(),
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'open_due' => $openDue,
            ],
            'openSales' => $openSales,
            'paymentHistory' => $paymentHistory,
            'paymentMethods' => PaymentMethods::options(),
            'canManage' => auth()->user()?->can('customers.manage') ?? false,
        ]);
    }
}
