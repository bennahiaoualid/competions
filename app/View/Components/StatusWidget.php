<?php

namespace App\View\Components;

use Illuminate\View\Component;

class StatusWidget extends Component
{
    public $status;
    public $text;
    public $message;

    /**
     * Create a new component instance.
     *
     * @param string $status
     * @param string $message
     */
    public function __construct($status,$text, $message = '')
    {
        $this->status = $status;
        $this->text = $text;
        $this->message = $message;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.ui_widgets.status-widget');
    }
}
