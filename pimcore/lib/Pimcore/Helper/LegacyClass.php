<?php

namespace Pimcore\Helper;

class LegacyClass {

    public function __construct()
    {
        self::throwException();
    }

    public static function __callStatic($name, $arguments)
    {
        self::throwException();
    }

    protected static function throwException() {
        throw new \Exception("Your're using Pimcore in without the PimcoreLegacyBundle and therefore without ZF Support!");
    }
}
