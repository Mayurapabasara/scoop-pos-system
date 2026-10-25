<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\CompleteSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class SaleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Sale::class);

        $sales = Sale::query()
            ->with(['customer', 'user', 'lines.item', 'payments'])
            ->when(
                $request->user()->role->value === 'cashier',
                fn ($query) => $query->where('user_id', $request->user()->id),
            )
            ->latest('id')
            ->get();

        return SaleResource::collection($sales);
    }

    public function store(StoreSaleRequest $request, CompleteSale $completeSale): JsonResponse
    {
        $sale = $completeSale->handle($request->user(), $request->validated());

        return (new SaleResource($sale))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('sales.show', $sale));
    }

    public function show(Sale $sale): SaleResource
    {
        Gate::authorize('view', $sale);

        return new SaleResource($sale->load(['customer', 'user', 'lines.item', 'payments']));
    }
}
