<?php
use Scoop\Validation\Email;
use Scoop\Validation\Equals;
use Scoop\Validation\Length;
use Scoop\Validation\Max;
use Scoop\Validation\MaxLength;
use Scoop\Validation\Min;
use Scoop\Validation\MinLength;
use Scoop\Validation\Number;
use Scoop\Validation\Pattern;
use Scoop\Validation\Range;
use Scoop\Validation\Required;
use Scoop\Validation\Same;

return array(
    'es' => array(
        'fail' => array(
            Required::class => 'Complete este campo',
            Length::class => 'La longitud del texto debe encontrarse en un rango entre {min} y {max} (actualmente tiene {length})',
            MaxLength::class => 'Disminuya la longitud del texto a {max} caracteres como maximo (actualmente tiene {length})',
            MinLength::class => 'Aumenta la longitud del texto a {min} caracteres como minimo (actualmente tiene {length})',
            Range::class => 'El valor debe encontrarse en un rango entre {min} y {max}',
            Number::class => 'El campo no es un valor númerico valido',
            Max::class => 'El valor debe ser inferior o igual a {max}',
            Min::class => 'El valor debe ser superior o igual a {min}',
            Email::class => 'Introduzca una dirección de correo valida',
            Pattern::class => 'Utiliza un formato que coincida con el solicitado',
            Equals::class => 'El campo no coincide con {subject}',
            Same::class => 'El campo no coincide con el valor de {fail}'
        )
    ),
    'en' => array(
        'fail' => array(
            Required::class => 'Please fill out this field',
            Length::class => 'Please modify the length of this text to a range between {min} and {max} (currently {length})',
            MaxLength::class => 'Please shorten this text to {max} characters or less (currently {length})',
            MinLength::class => 'Please lengthen this text to {min} characters or more (currently {length})',
            Range::class => 'Value must be in a range between {min} and {max}',
            Number::class => 'Please fill out a valid numerical value',
            Max::class => 'Value must be less than or equal to {max}',
            Min::class => 'Value must be greater than or equal to {min}',
            Email::class => 'Please include an @ in the email address',
            Pattern::class => 'Please match the request format',
            Equals::class => 'Please match the field to {subject}',
            Same::class => 'Please match the field to value of {fail}'
        )
    )
);
