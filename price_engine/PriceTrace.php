<?php
declare(strict_types=1);

class PriceTrace
{
    private array $steps = [];

    public function add(string $title, mixed $value): void
    {
        $this->steps[] = [
            'title' => $title,
            'value' => $value
        ];
    }

    public function all(): array
    {
        return $this->steps;
    }

    public function clear(): void
    {
        $this->steps = [];
    }
}