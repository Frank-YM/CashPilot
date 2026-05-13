<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comprobante extends Model
{
    protected $fillable = ['movimiento_id', 'nombre_original', 'nombre_archivo', 'ruta', 'tipo_mime', 'tamano'];

    protected $casts = ['tamano' => 'integer'];

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(Movimiento::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->ruta);
    }

    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->nombre_original, PATHINFO_EXTENSION));
    }

    public function getTipoVisualAttribute(): string
    {
        return in_array($this->tipo_mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) ? 'image' : 'file';
    }
}