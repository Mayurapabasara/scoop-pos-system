<?php

namespace App\Http\Requests\Item;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Item::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'unit_price' => ['required', 'decimal:0,2', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
