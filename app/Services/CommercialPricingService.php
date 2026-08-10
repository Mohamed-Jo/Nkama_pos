<?php

namespace App\Services;

use App\Models\CommercialPriceRule;
use App\Models\CommercialPromotion;
use App\Models\Customer;
use App\Models\Product;

class CommercialPricingService
{
    public function priceFor(Product $product, ?Customer $customer = null): array
    {
        $basePrice = round((float) $product->selling_price, 2);
        $price = $basePrice;
        $source = 'normal';

        $rule = $this->priceRule($product, $customer);
        if ($rule) {
            $price = round((float) $rule->unit_price, 2);
            $source = 'tabela';
        } else {
            $promotion = $this->promotion($product);
            if ($promotion) {
                if ($promotion->fixed_price !== null) {
                    $price = round((float) $promotion->fixed_price, 2);
                } else {
                    $price = round($basePrice * max(0, 1 - ((float) $promotion->discount_percent / 100)), 2);
                }
                $source = 'promocao';
            }
        }

        return [
            'base_price' => $basePrice,
            'unit_price' => max($price, 0),
            'source' => $source,
        ];
    }

    public function customerDiscount(?Customer $customer): float
    {
        return round(min(max((float) ($customer?->discount_percent ?? 0), 0), 100), 2);
    }

    private function priceRule(Product $product, ?Customer $customer): ?CommercialPriceRule
    {
        if (! $customer) {
            return null;
        }

        return CommercialPriceRule::query()
            ->where('product_id', $product->id)
            ->where('active', true)
            ->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)
                    ->orWhere(function ($inner) use ($customer) {
                        $inner->whereNull('customer_id')
                            ->whereNotNull('price_table')
                            ->where('price_table', $customer->price_table);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today());
            })
            ->orderByRaw('CASE WHEN customer_id IS NULL THEN 1 ELSE 0 END')
            ->latest()
            ->first();
    }

    private function promotion(Product $product): ?CommercialPromotion
    {
        return CommercialPromotion::query()
            ->where('active', true)
            ->where(function ($query) use ($product) {
                $query->where('product_id', $product->id)
                    ->orWhere('category_id', $product->category_id);
            })
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', today());
            })
            ->orderByRaw('CASE WHEN product_id IS NULL THEN 1 ELSE 0 END')
            ->latest()
            ->first();
    }
}