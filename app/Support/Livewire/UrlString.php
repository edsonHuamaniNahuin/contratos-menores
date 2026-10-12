<?php

namespace App\Support\Livewire;

use Livewire\Features\SupportQueryString\BaseUrl;

/**
 * Variante segura de #[Url] para propiedades tipadas `string`.
 *
 * Livewire 4 hace `json_decode` del valor del query string: un parámetro
 * tipo array (ej: ?palabraClave[]=x) llega como array y asignarlo a una
 * propiedad string lanza TypeError → error 500.
 *
 * Este atributo ignora arrays/objetos y conserva el default de la propiedad.
 */
#[\Attribute]
class UrlString extends BaseUrl
{
    public function setValue($value, ?bool $nullable = false)
    {
        if (is_array($value) || is_object($value)) {
            $actual = $this->getValue();
            $value = is_string($actual) ? $actual : '';
        }

        parent::setValue($value, $nullable);
    }
}
