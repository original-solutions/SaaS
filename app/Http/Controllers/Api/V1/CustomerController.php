<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerRequest;
use App\Models\Customer;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->applySearch($request, ['name'])
            ->applySort($request, ['name', 'created_at'])
            ->applyFilters($request, ['status']);

        return response()->json($query->paginate($request->integer('per_page', 15)));
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $model = Customer::create([
            'tenant_id' => app(TenantContext::class)->id(),
            ...$request->validated(),
        ]);

        return response()->json(['data' => $model], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        return response()->json(['data' => $customer]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return response()->json(['data' => $customer->fresh()]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        $customer->delete();

        return response()->json(null, 204);
    }
}
