<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CollapsibleCard extends Component
{
    public $title;
    public $type;
    public $headerClass;
    public $contentClass;
    public $isopen;
    public function __construct($title, $type = 'primary', $isopen = true)
    {
        $this->title = $title;
        $this->type = $type;

        // Assign Tailwind CSS classes based on type
        $this->headerClass = $this->getHeaderClass($type);
        $this->contentClass = $this->getContentClass($type);
        $this->isopen = $isopen;
    }

    protected function getHeaderClass($type)
    {
        switch ($type) {
            case 'info':
                return 'bg-blue-500';
            case 'primary':
                return 'bg-primary';
            case 'danger':
                return 'bg-danger';
            case 'success':
                return 'bg-success';
            case 'warning':
                return 'bg-warning';
            default:
                return 'bg-primary';
        }
    }

    protected function getContentClass($type)
    {
        switch ($type) {
            case 'info':
                return 'border-blue-500';
            case 'primary':
                return 'border-primary';
            case 'danger':
                return 'border-danger';
            case 'success':
                return 'border-success';
            case 'warning':
                return 'border-warning';
            default:
                return 'border-primary';
        }
    }

    public function render()
    {
        return view('components.ui_widgets.collapsible-card');
    }
}
