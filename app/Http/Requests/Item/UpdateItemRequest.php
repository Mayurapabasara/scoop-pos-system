<?php

namespace App\Http\Requests\Item;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof Item && ($this->user()?->can('update', $item) ?? false);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'unit_price' => ['sometimes', 'required', 'decimal:0,2', 'min:0'],
            'stock_quantity' => ['sometimes', 'required', 'integer', 'min:0', 'max:4294967295'],
        ];
    }
}
