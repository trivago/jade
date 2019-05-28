<?php

namespace App\Validator;

class BlacklistWordsValidator
{
    const BLACKLISTED_WORDS = ['fuck', 'shit', 'cyka'];

    public function validateBlacklistedWords($string)
    {
        $string = strtolower($string);
        foreach (self::BLACKLISTED_WORDS as $word) {
            if (strpos($string, $word) !== false) {
                throw new \InvalidArgumentException(sprintf('The word "%s" can not be used in any string', $word));
            }
        }
    }
}