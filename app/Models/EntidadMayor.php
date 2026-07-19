<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntidadMayor extends Model
{
    protected $table = 'entidades_mayores';

    protected $fillable = ['nombre', 'ruc'];
}
