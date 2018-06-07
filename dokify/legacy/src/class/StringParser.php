<?php

class StringParser
{
    public static function isEmail($email)
    {
        if (!isset($email)) {
            return false;
        }

        return (bool) preg_match("/". elemento::getEmailRegExp() ."/", $email);
    }
}
