<?php

return array(
    'failures' => array(
        'Scoop\Validation\Rule\Date' => 'Invalid date',
        'Scoop\Validation\Rule\Required' => 'Please fill out this field',
        'Scoop\Validation\Rule\Length' => 'Please modify the length of this text to a range between {min} and {max} (currently {length})',
        'Scoop\Validation\Rule\MinLength' => 'Please lengthen this text to {min} characters or more (currently {length})',
        'Scoop\Validation\Rule\MaxLength' => 'Please shorten this text to {max} characters or less (currently {length})',
        'Scoop\Validation\Rule\Range' => 'Value must be in a range between {min} and {max}',
        'Scoop\Validation\Rule\Number' => 'Please fill out a valid numerical value',
        'Scoop\Validation\Rule\Max' => 'Value must be less than or equal to {max}',
        'Scoop\Validation\Rule\Min' => 'Value must be greater than or equal to {min}',
        'Scoop\Validation\Rule\Email' => 'Please include a email address valid format',
        'Scoop\Validation\Rule\Pattern' => 'Please match the request format',
        'Scoop\Validation\Rule\Equals' => 'Please match the field to {subject}',
        'Scoop\Validation\Rule\Same' => 'Please match the field to value of {fail}',
        'Scoop\Validation\Rule\Lowercase' => 'Field must be lower case',
        'Scoop\Validation\Rule\Uppercase' => 'Field must be upper case',
        'Scoop\Validation\Rule\Url' => 'Field must be a valid URL',
        'Scoop\Validation\Rule\In' => 'Field must be one of the following values: {allowed}'
    )
);
