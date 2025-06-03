<?php

namespace App\Helpers;

/** Class Help general */
class Help
{
    /**
     * Generates a random external id string with a given text.
     * If $text is given, it will be prefixed with the generated external id.
     * The generated external id is a 10 character string.
     *
     * @param string|null $text The text to be prefixed.
     * @return string A random external id string.
     */
    public static function generateExtId($text = null): string
    {
        if (isset($text)) {
            $text .= '_';
        }

        return $text . substr(md5(uniqid(rand(), true)), 0, 10);
    }
}
