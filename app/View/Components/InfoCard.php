<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class InfoCard extends Component
{
    /**
     * Create a new component instance.
     */
    public $type;
    public $size;
    public $content;
    public $value;

    public function __construct($type = 'info', $size = 'md', $content = '', $value = '')
    {
        $this->type = $type;
        $this->size = $size;
        $this->content = $content;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.cards.info-card');
    }
}
