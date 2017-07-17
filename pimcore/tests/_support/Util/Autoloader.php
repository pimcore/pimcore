<?php

namespace Pimcore\Tests\Util;

use Codeception\Util\Autoload;

class Autoloader extends Autoload {

    protected static $reg = false;

    public static function addNamespace($prefix, $base_dir, $prepend = false)
    {
        if (!self::$reg) {
            spl_autoload_register([__CLASS__, 'load'], true, true);
            self::$reg = true;
        }

        parent::addNamespace($prefix, $base_dir, $prepend);
    }
}
