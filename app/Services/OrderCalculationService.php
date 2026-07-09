<?php

namespace App\Services;

class OrderCalculationService
{
    /**
     * @param  array<int, array{quantity?: float|int, unit_price?: float|int, total_price?: float|int}>  $items
     * @return array{total_amount: float, final_amount: float, carrier_cod_amount: float}
     */
    public function calculateTotals(array $items, float $orderCommission = 0, float $discount = 0): array
    {
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $quantity = max(0, (float) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $lineTotal = isset($item['total_price'])
                ? max(0, (float) $item['total_price'])
                : round($quantity * $unitPrice, 2);

            $totalAmount += $lineTotal;
        }

        $totalAmount = round($totalAmount, 2);
        $orderCommission = max(0, round($orderCommission, 2));
        $discount = max(0, round($discount, 2));
        $finalAmount = round(max(0, $totalAmount - $orderCommission - $discount), 2);
        $carrierCodAmount = round(max(0, $totalAmount - $discount), 2);

        return [
            'total_amount' => $totalAmount,
            'final_amount' => $finalAmount,
            'carrier_cod_amount' => $carrierCodAmount,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyCalculatedAmounts(array $data): array
    {
        $items = $data['items'] ?? [];
        $totals = $this->calculateTotals(
            is_array($items) ? $items : [],
            (float) ($data['delivery_fee'] ?? 0),
            (float) ($data['discount'] ?? 0),
        );

        $data['total_amount'] = $totals['total_amount'];
        $data['final_amount'] = $totals['final_amount'];

        return $data;
    }

    public function calculateLineTotal(float|int $quantity, float|int $unitPrice): float
    {
        return round(max(0, (float) $quantity) * max(0, (float) $unitPrice), 2);
    }
}
