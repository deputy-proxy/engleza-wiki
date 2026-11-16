<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use Livewire\Component;

class InteractiveCards extends Component
{
    public Resource $resource;

    public int $currentIndex = 0;

    public bool $revealed = false;

    public array $cards = [];

    public function mount(Resource $resource): void
    {
        $this->resource = $resource;
        $this->cards = $resource->cardResource?->cards()->orderBy('sort_order')->get()->map(fn ($c) => ['id' => $c->id, 'front' => $c->front, 'back' => $c->back])->all() ?? [];
    }

    public function reveal(): void
    {
        $this->revealed = ! $this->revealed;
    }

    public function next(): void
    {
        if ($this->currentIndex < count($this->cards) - 1) {
            $this->currentIndex++;
            $this->revealed = false;
        }
    }

    public function prev(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->revealed = false;
        }
    }

    public function jump(int $i): void
    {
        if ($i >= 0 && $i < count($this->cards)) {
            $this->currentIndex = $i;
            $this->revealed = false;
        }
    }

    public function render()
    {
        return view('livewire.resources.interactive-cards');
    }
}
