<?php
/**
 * Created by JetBrains PhpStorm.
 * User: jaichhorn
 * Date: 08.02.13
 * Time: 09:10
 * To change this template use File | Settings | File Templates.
 */
class Pimcore_Tool_Cast {

    public static function castToClass($class, $object)
    {
        return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', serialize($object)));
    }
}
