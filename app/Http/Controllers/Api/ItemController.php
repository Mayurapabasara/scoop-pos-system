<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\IndexItemRequest;
use App\Http\Requests\Item\StoreItemRequest;
use App\Http\Requests\Item\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ItemController extends Controller
{
    public function index(IndexItemRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Item::class);

        $search = $request->validated('search');
        $items = Item::query()
            ->with('category')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->get();

        return ItemResource::collection($items);
    }

    public function store(StoreItemRequest $request): JsonResponse
    {
        $item = Item::query()->create($request->validated())->load('category');

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201)
            ->header('Location', route('items.show', $item));
    }

    public function show(Item $item): ItemResource
    {
        Gate::authorize('view', $item);

        return new ItemResource($item->load('category'));
    }

    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $item->update($request->validated());

        return new ItemResource($item->load('category'));
    }

    public function destroy(Item $item): Response
    {
        Gate::authorize('delete', $item);

        $item->delete();

        return response()->noContent();
    }
}
