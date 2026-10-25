<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteSale
{
    /**
     * @param  array{customer_id?: int|null, items: array<int, array{item_id: int, quantity: int}>, payments: array<int, array{method: string, amount: string, transaction_reference?: string|null}>}  $data
     */
    public function handle(User $user, array $data): Sale
    {
        return DB::transaction(function () use ($data, $user): Sale {
            $quantities = collect($data['items'])->mapWithKeys(
                fn (array $line): array => [$line['item_id'] => $line['quantity']],
            );
            $items = Item::query()
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotalCents = 0;

            foreach ($quantities as $itemId => $quantity) {
                $item = $items->get($itemId);

                if ($item === null) {
                    throw ValidationException::withMessages([
                        'items' => ['One or more inventory items are no longer available.'],
                    ]);
                }

                if ($item->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for {$item->name}."],
                    ]);
                }

                $subtotalCents += $this->toCents($item->unit_price) * $quantity;
            }

            $paymentCents = collect($data['payments'])
                ->sum(fn (array $payment): int => $this->toCents($payment['amount']));

            if ($paymentCents !== $subtotalCents) {
                throw ValidationException::withMessages([
                    'payments' => ['The payment total must exactly match the sale total.'],
                ]);
            }

            $sale = Sale::query()->create([
                'receipt_number' => 'SALE-'.Str::upper((string) Str::ulid()),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $user->id,
                'subtotal' => $this->fromCents($subtotalCents),
                'total' => $this->fromCents($subtotalCents),
            ]);

            foreach ($quantities as $itemId => $quantity) {
                $item = $items->get($itemId);
                $lineTotalCents = $this->toCents($item->unit_price) * $quantity;

                $sale->lines()->create([
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'unit_price' => $item->unit_price,
                    'quantity' => $quantity,
                    'line_total' => $this->fromCents($lineTotalCents),
                ]);
                $item->decrement('stock_quantity', $quantity);
            }

            foreach ($data['payments'] as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'transaction_reference' => $payment['transaction_reference'] ?? null,
                ]);
            }

            return $sale->load(['customer', 'user', 'lines.item', 'payments']);
        });
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
