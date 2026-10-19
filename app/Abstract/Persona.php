<?php

namespace App\Abstract;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

abstract class Persona extends Model
{
    protected $fillable = [
        'dni',
        'nombre',
        'apellido',
        'fecha_nacimiento',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function nombreCompleto(): string
    {
        return "{$this->nombre} {$this->apellido}";
    }

    public function edad(): int
    {
        return $this->fecha_nacimiento->age;
    }

    public function scopeBuscarPorDni($query, string $dni)
    {
        return $query->where('dni', $dni);
    }

    protected function nombre(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
        );
    }
}