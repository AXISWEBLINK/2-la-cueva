<?php
declare(strict_types=1);

class OverrideResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {
        if (empty($context['override_active']) || empty($context['override_id'])) {
            $result->trace->add('Override', 'NO');
            return $result;
        }

        if (!$this->isWithinDateWindow(
            $context['override_starts_at'] ?? null,
            $context['override_ends_at'] ?? null
        )) {
            $result->trace->add('Override', 'NO VIGENTE');
            return $result;
        }

        $overridePrice = $this->resolveOverridePrice($context);

        if ($overridePrice === null) {
            $result->trace->add('Override', 'SIN PRECIO');
            return $result;
        }

        $result->hasOverride = true;
        $result->overridePrice = $overridePrice;
        $result->finalPrice = $overridePrice;

        $result->trace->add('Override', 'APLICADO');
        $result->trace->add('Precio Override', $result->overridePrice);

        return $result;
    }

    private function resolveOverridePrice(array $context): ?float
    {
        if ($context['manual_price_gross'] !== null) {
            return round((float)$context['manual_price_gross'], 2);
        }

        if ($context['manual_price_net'] === null) {
            return null;
        }

        $manualPriceNet = (float)$context['manual_price_net'];
        $vatPercent = (float)($context['vat_percent'] ?? 0);

        return round($manualPriceNet * (1 + ($vatPercent / 100)), 2);
    }

    private function isWithinDateWindow(?string $startsAt, ?string $endsAt): bool
    {
        $now = new DateTimeImmutable();

        if ($startsAt !== null && $startsAt !== '') {
            $start = new DateTimeImmutable($startsAt);

            if ($now < $start) {
                return false;
            }
        }

        if ($endsAt !== null && $endsAt !== '') {
            $end = new DateTimeImmutable($endsAt);

            if ($now > $end) {
                return false;
            }
        }

        return true;
    }
}