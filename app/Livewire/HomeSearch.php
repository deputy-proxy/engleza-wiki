<?php

namespace App\Livewire;

use App\Models\Resource;
use App\Models\Test;
use Livewire\Attributes\Url;
use Livewire\Component;

class HomeSearch extends Component
{
    #[Url(as: 'q', except: '')]
    public string $q = '';

    public array $resources = [];

    public array $tests = [];

    public bool $open = false;

    public function updatedQ(): void
    {
        $term = trim($this->q);
        if (mb_strlen($term) < 2) {
            $this->resources = [];
            $this->tests = [];
            $this->open = false;

            return;
        }
        $this->resources = Resource::published()
            ->where(fn ($x) => $x->where('title', 'like', "%$term%")->orWhere('excerpt', 'like', "%$term%")->orWhereHas('translations', fn ($translations) => $translations->where('locale', app()->getLocale())->where('status', 'reviewed')->whereIn('field', ['title', 'excerpt'])->where('value', 'like', "%$term%")))
            ->orderByDesc('featured')->orderByDesc('published_at')
            ->limit(6)->get()
            ->map(fn ($r) => ['title' => $r->translated('title'), 'slug' => $r->slug, 'type' => $r->type, 'level' => $r->level?->translated('name')])
            ->all();
        $this->tests = Test::published()->where(fn ($query) => $query->where('title', 'like', "%$term%")->orWhereHas('translations', fn ($translations) => $translations->where('locale', app()->getLocale())->where('status', 'reviewed')->where('field', 'title')->where('value', 'like', "%$term%")))
            ->limit(3)->get()
            ->map(fn ($t) => ['title' => $t->translated('title'), 'slug' => $t->slug, 'level' => $t->level?->translated('name')])
            ->all();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function focus(): void
    {
        if (mb_strlen(trim($this->q)) >= 2) {
            $this->open = true;
        }
    }

    public function render()
    {
        return view('livewire.home-search');
    }
}
