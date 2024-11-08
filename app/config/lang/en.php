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
        Date::class => 'Invalid date',
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
        Same::class => 'Please match the field to value of {fail}',
        Lowercase::class => 'Field must be lower case',
        Uppercase::class => 'Field must be upper case'
    )
);
