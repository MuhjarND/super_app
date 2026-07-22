<?php

namespace App\Support;

class PersonNameFormatter
{
    public static function withoutTitles($name)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        // Academic degrees in employee data are consistently stored after a comma.
        $name = trim(explode(',', $name, 2)[0]);
        $prefixes = '(?:Prof(?:esor)?|Drs?|Dra|Ir|Hj?|K\.?H|Raden|R\.?A|R\.?Ayu)';

        do {
            $previous = $name;
            $name = preg_replace('/^' . $prefixes . '\.?\s+/i', '', $name);
            $name = trim((string) $name);
        } while ($name !== $previous && $name !== '');

        return preg_replace('/\s+/', ' ', $name);
    }
}
