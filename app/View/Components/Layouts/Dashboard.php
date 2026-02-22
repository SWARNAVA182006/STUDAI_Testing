<?php

declare(strict_types=1);

namespace App\View\Components\Layouts;

use Illuminate\View\Component;
use Illuminate\View\View;

class Dashboard extends Component
{
    public function __construct(
        public string $title = 'Dashboard',
    ) {}

    public function render(): View
    {
        return view('layouts.dashboard');
    }
}
