<?php
declare(strict_types=1);

class RoundingResolver
{
    public function resolve(
        array $context,
        PriceResult $result
    ): PriceResult {
        $applyRounding = !empty($context['apply_rounding']);
        $roundingMode = (string)($context['rounding_mode'] ?? 'none');
        $roundingStep = max((float)($context['rounding_step'] ?? 1), 0.01);
        $showDecimals = !empty($context['show_decimals']);
        $priceBeforeRounding = $result->finalPrice;

        if (!$applyRounding || $roundingMode === 'none') {
            $result->finalPrice = $this->normalizeDecimals($result->finalPrice, $showDecimals);
            $result->trace->add('Redondeo', 'NO');
            $result->trace->add('Precio Final', $result->finalPrice);
            return $result;
        }

        $roundedPrice = match ($roundingMode) {
            'round' => round($priceBeforeRounding / $roundingStep) * $roundingStep,
            'ceil' => ceil($priceBeforeRounding / $roundingStep) * $roundingStep,
            'floor' => floor($priceBeforeRounding / $roundingStep) * $roundingStep,
            'psychological' => $this->applyPsychologicalRounding($priceBeforeRounding, $roundingStep),
            default => $priceBeforeRounding,
        };

        $result->finalPrice = $this->normalizeDecimals(max($roundedPrice, 0), $showDecimals);

        $result->trace->add('Redondeo', strtoupper($roundingMode));
        $result->trace->add('Precio antes redondeo', round($priceBeforeRounding, 2));
        $result->trace->add('Precio Final', $result->finalPrice);

        return $result;
    }

    private function normalizeDecimals(float $value, bool $showDecimals): float
    {
        return $showDecimals
            ? round($value, 2)
            : round($value, 0);
    }

    private function applyPsychologicalRounding(float $value, float $roundingStep): float
    {
        if ($value <= 1) {
            return 0.0;
        }

        $base = $this->resolvePsychologicalBase($value, $roundingStep);
        $candidate = (floor($value / $base) * $base) - 1;

        if ($candidate >= $value) {
            $candidate -= $base;
        }

        return max($candidate, 0.0);
    }

    private function resolvePsychologicalBase(float $value, float $roundingStep): float
    {
        if ($value >= 1000) {
            return max(1000.0, ceil($roundingStep));
        }

        if ($value >= 100) {
            return max(100.0, ceil($roundingStep));
        }

        if ($value >= 10) {
            return max(10.0, ceil($roundingStep));
        }

        return max(1.0, ceil($roundingStep));
    }
}