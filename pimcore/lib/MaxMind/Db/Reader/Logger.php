<?php

namespace MaxMind\Db\Reader;

// This is only used internally as a centralized way to handle debugging
// information.
class Logger
{
    public static function log($name = "\n", $message = null)
    {
        if ($message === null) {
            print("$name\n");
        } else {
            print("$name: $message\n");
        }
    }

    public static function logByte($name, $byte)
    {
        self::log($name, dechex($byte));
    }

    public static function logBytes($name, $bytes)
    {
        $message = implode(',', array_map('dechex', unpack('C*', $bytes)));
        self::log($name, $message);
    }
}
