<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Logger;

class ClassResolver
{
    private static $cache;

    protected static function resolve($class, callable $validationCallback = null)
    {
        if ($class) {
            if (isset(self::$cache[$class])) {
                return self::$cache[$class];
            }
            if (strpos($class, '@') === 0) {
                $serviceName = substr($class, 1);
                try {
                    $service = \Pimcore::getKernel()->getContainer()->get($serviceName);
                    return self::returnValidServiceOrNull($service, $validationCallback);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            } else {
                $service = new $class;
                return self::returnValidServiceOrNull($service, $validationCallback);
            }
        }

        return null;
    }

    private static function returnValidServiceOrNull($service, callable $validationCallback = null) {
        if($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}