<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categoria extends Model
{
    use SoftDeletes;

    protected $fillable = ['nombre', 'tipo', 'color'];

    public function movimientos(): HasMany
    {
        return $this->hasMany(Movimiento::class);
    }

    public static function getCategoriasPorTipo(): array
    {
        return self::all()->groupBy('tipo')->map(fn($cats) => $cats->mapWithKeys(fn($c) => [$c->id => $c->nombre]))->toArray();
    }
}