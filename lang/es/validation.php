<?php

return [
    'required'             => 'El campo :attribute es obligatorio.',
    'string'               => 'El campo :attribute debe ser una cadena de texto.',
    'email'                => 'El campo :attribute debe ser una dirección de email válida.',
    'unique'               => 'El valor del campo :attribute ya está en uso.',
    'exists'               => 'El valor seleccionado en :attribute no es válido.',
    'numeric'              => 'El campo :attribute debe ser numérico.',
    'integer'              => 'El campo :attribute debe ser un número entero.',
    'min'                  => [
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string'  => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'max'                  => [
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string'  => 'El campo :attribute no debe tener más de :max caracteres.',
    ],
    'between'              => [
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
    ],
    'confirmed'            => 'La confirmación del campo :attribute no coincide.',
    'date'                 => 'El campo :attribute no es una fecha válida.',

    'attributes' => [
        'Unathenticated' => 'No autenticado',
        'name'           => 'nombre',
        'email'          => 'correo electrónico',
        'password'       => 'contraseña',
        'role_id'        => 'rol',
        'station_id'     => 'estación',
        'observed_at'    => 'fecha de observación',
        'location'       => 'ubicación',
        'altitude'       => 'altitud',
        'temperature'    => 'temperatura',
        'humidity'       => 'humedad',
        'pressure'       => 'presión',
        'wind_speed'     => 'velocidad del viento',
        'wind_direction' => 'dirección del viento',
        'precipitation'  => 'precipitación',
        'title'          => 'título',
        'message'        => 'mensaje',
        'level'          => 'nivel',
        'user_id'        => 'usuario',
        'start_date'     => 'fecha de inicio',
        'end_date'       => 'fecha de fin',
    ],
];
