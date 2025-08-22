<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use Closure;

class Role extends Component
{
    public array $roles = [];

    public function __construct(string|array $role = null, string|array $roles = null)
    {
        $input = $roles ?? $role ?? [];
        if (is_string($input)) {
            $input = array_map('trim', explode(',', $input));
        }
        $this->roles = array_values(array_filter((array) $input));
    }

    public function render(): View|Closure|string
    {
        return view('components.role');
    }
}
