<?php

namespace App\Support\Livewire;

use Livewire\Features\SupportQueryString\BaseUrl;

/**
 * Variante segura de #[Url] para propiedades tipadas `int`.
 *
 * Livewire 4 asigna el valor CRUDO del query string cuando `json_decode`
 * no lo convierte (ej: ?pagina=abc, ?pagina=, ?pagina[]=1). Eso lanza
 * "Cannot assign string/array to property of type int" → error 500.
 *
 * Este atributo coacciona ANTES de asignar: valores no numéricos vuelven
 * al default de la propiedad; los numéricos se truncan a int (con min/max
 * opcionales).
 */
#[\Attribute]
class UrlInt extends BaseUrl
{
    public function __construct(
        $as = null,
        $history = false,
        $keep = false,
        $except = null,
        $nullable = null,
        public ?int $min = null,
        public ?int $max = null,
    ) {
        parent::__construct($as, $history, $keep, $except, $nullable);
    }

    public function setValue($value, ?bool $nullable = false)
    {
        if ($value === null) {
            $value = $nullable ? null : $this->valorPorDefecto();
        } elseif (! is_numeric($value)) {
            $value = $this->valorPorDefecto();
        } else {
            $value = (int) $value;

            if ($this->min !== null) {
                $value = max($this->min, $value);
            }
            if ($this->max !== null) {
                $value = min($this->max, $value);
            }
        }

        parent::setValue($value, $nullable);
    }

    /**
     * Default declarado en la propiedad (ej: `public int $pagina = 1` → 1).
     */
    protected function valorPorDefecto(): int
    {
        $actual = $this->getValue();

        return is_int($actual) ? $actual : 0;
    }
}
