<?php

namespace App\Models\Workshop;

abstract class ImmutableRecord extends Record
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('El registro confirmado es inmutable. Usa un ajuste trazable.'));
        static::deleting(fn () => throw new \LogicException('No se eliminan registros confirmados.'));
    }
}
