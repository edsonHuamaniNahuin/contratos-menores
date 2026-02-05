<?php

use Livewire\Component;

new class extends Component
{
    public $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }
};
?>

<div>
    <div style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; border: 2px solid #3498db;">
        <h4 style="margin-bottom: 15px; color: #2c3e50;">⚡ Componente Livewire Activo</h4>

        <p style="margin-bottom: 15px;">
            Este componente se actualiza <strong>sin recargar la página</strong> (AJAX automático).
        </p>

        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
            <button wire:click="decrement" class="btn" style="background-color: #e74c3c;">
                -
            </button>

            <span style="font-size: 32px; font-weight: bold; color: #2c3e50; min-width: 60px; text-align: center;">
                {{ $count }}
            </span>

            <button wire:click="increment" class="btn" style="background-color: #27ae60;">
                +
            </button>
        </div>

        <p style="font-size: 13px; color: #7f8c8d;">
            Contador de ejemplo - Click en los botones para ver Livewire en acción
        </p>
    </div>
</div>
