<?php

namespace App\View\Components;

use Illuminate\View\Component;

class CollapsibleCard extends Component
{
    public $title;
    public $type;
    public $headerClass;
    public $contentClass;

    public function __construct($title, $type = 'primary')
    {
        $this->title = $title;
        $this->type = $type;

        // Assign Tailwind CSS classes based on type
        $this->headerClass = $this->getHeaderClass($type);
        $this->contentClass = $this->getContentClass($type);
    }

    protected function getHeaderClass($type)
    {
        switch ($type) {
            case 'info':
                return 'bg-blue-500';
            case 'primary':
                return 'bg-indigo-600';
            case 'danger':
                return 'bg-red-600';
            case 'success':
                return 'bg-green-500';
            case 'warning':
                return 'bg-yellow-500';
            default:
                return 'bg-indigo-600';
        }
    }

    protected function getContentClass($type)
    {
        switch ($type) {
            case 'info':
                return 'border-blue-500';
            case 'primary':
                return 'border-indigo-600';
            case 'danger':
                return 'border-red-600';
            case 'success':
                return 'border-green-500';
            case 'warning':
                return 'border-yellow-500';
            default:
                return 'border-indigo-600';
        }
    }

    public function render()
    {
        return view('components.ui_widgets.collapsible-card');
    }
}
