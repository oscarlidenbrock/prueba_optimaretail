<?php

namespace App\Exceptions;

use DomainException;

class IncidentStatusConflict extends DomainException
{
    public function __construct()
    {
        parent::__construct('El cambio de estado solicitado no está permitido para el estado actual de la incidencia.');
    }
}
