<?php

declare(strict_types=1);

namespace App\Livewire\Mobile;

use Livewire\Component;

class InstallPrompt extends Component
{
    public bool $isDismissed = false;
    
    public function dismiss(): void
    {
        $this->isDismissed = true;
        // The actual dismissal is handled in JavaScript with localStorage
    }
    
    public function render()
    {
        return view('livewire.mobile.install-prompt');
    }
}
