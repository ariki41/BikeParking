<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectList extends Component
{
    public $options;

    public $name;

    public $selected;

    public $default;

    /**
     * Create a new component instance.
     */
    public function __construct($options = [], $name = '', $selected = null, $default = '')
    {
        $this->options = $options;
        $this->name = $name;
        $this->selected = $selected;
        $this->default = $default;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.select-list');
    }
}
