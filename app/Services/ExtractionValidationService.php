<?php

namespace App\Services;

use App\Enums\DocumentFlag;
use App\Support\Money;
use Carbon\CarbonImmutable;

class ExtractionValidationService
{
    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public function flags(array $data): array
    {
        $flags = [];
        $tolerance = (float) config('ainota.validation.amount_tolerance');
        $low = (float) config('ainota.validation.low_confidence');

        $subtotal = Money::toFloat($data['subtotal'] ?? 0);
        $discount = Money::toFloat($data['discount'] ?? 0);
        $tax = Money::toFloat($data['tax'] ?? 0);
        $service = Money::toFloat($data['service_charge'] ?? 0);
        $grand = $data['grand_total'] ?? null;

        if ($grand === null) {
            $flags[] = DocumentFlag::MissingGrandTotal->value;
        } else {
            $expected = $subtotal - $discount + $tax + $service;
            if (! Money::equal($expected, $grand, $tolerance)) {
                $flags[] = DocumentFlag::TotalMismatch->value;
            }
            if (Money::toFloat($grand) < 0) {
                $flags[] = DocumentFlag::TotalMismatch->value;
            }
        }

        $items = $data['items'] ?? [];
        if (is_array($items) && $items !== []) {
            $sum = 0.0;
            foreach ($items as $item) {
                $sum += Money::toFloat($item['amount'] ?? 0);
            }
            if ($subtotal > 0 && ! Money::equal($sum, $subtotal, $tolerance)) {
                $flags[] = DocumentFlag::ItemSumMismatch->value;
            }
        }

        $date = $data['transaction_date'] ?? null;
        if (is_string($date) && $date !== '') {
            try {
                $parsed = CarbonImmutable::parse($date);
                if ($parsed->isAfter(now()->addDays((int) config('ainota.validation.future_date_days')))) {
                    $flags[] = DocumentFlag::FutureDate->value;
                }
            } catch (\Throwable) {
                $flags[] = DocumentFlag::UnreadableDocument->value;
            }
        }

        $overall = (float) data_get($data, 'confidence.overall', 1);
        $totalConfidence = (float) data_get($data, 'confidence.total', 1);
        if ($overall < $low || $totalConfidence < $low) {
            $flags[] = DocumentFlag::LowConfidence->value;
        }

        return array_values(array_unique($flags));
    }
}
