<?php

namespace Filament\Forms\Components\Concerns;

use Closure;
use Filament\Forms\Components\Component;

trait CanLimitItemsLength
{
    protected int | Closure | null $maxItems = null;

    protected int | Closure | null $minItems = null;

    public function maxItems(int | Closure $count): static
    {
        $this->maxItems = $count;

        $this->rule('array');
        $this->rule(function (Component $component): string {
            $count = $component->getMaxItems();

            return "max:{$count}";
        });

        return $this;
    }

    public function minItems(int | Closure $count): static
    {
        $this->minItems = $count;

        $this->rule('array');
        $this->rule(function (Component $component): string {
            $count = $component->getMinItems();

            return "min:{$count}";
        });

        return $this;
    }

    public function getMaxItems(): ?int
    {
        return $this->evaluate($this->maxItems);
    }

    public function getMinItems(): ?int
    {
        return $this->evaluate($this->minItems);
    }

    public function getItemsCount(): int
    {
        $state = $this->getState();

        return is_array($state) ? count($state) : 0;
    }
}
