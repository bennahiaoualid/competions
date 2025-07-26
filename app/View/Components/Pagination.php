<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Pagination extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public $paginator,
        public array $perPageOptions = [5, 10, 25, 50, 100],
        public int $defaultPerPage = 5
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.pagination');
    }
} 