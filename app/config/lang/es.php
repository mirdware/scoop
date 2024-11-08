<?php

use Scoop\Validation\Date;
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
use Scoop\Validation\Lowercase;
use Scoop\Validation\Uppercase;

return array(
    'fail' => array(
        Date::class => 'Fecha invalida',
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
        Same::class => 'El campo no coincide con el valor de {fail}',
        Lowercase::class => 'El campo debe estar en minúscula',
        Uppercase::class => 'El campo debe estar en mayúscula'
    )
);
