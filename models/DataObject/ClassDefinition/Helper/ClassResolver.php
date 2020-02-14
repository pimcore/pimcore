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
            try {
                if (strpos($class, '@') === 0) {
                    $serviceName = substr($class, 1);
                    $service = \Pimcore::getKernel()->getContainer()->get($serviceName);
                } else {
                    $service = new $class;
                }

                self::$cache[$class] = self::returnValidServiceOrNull($service, $validationCallback);

                return self::$cache[$class];
            } catch (\Throwable $e) {
                Logger::error($e);
            }
        }

        return null;
    }

    private static function returnValidServiceOrNull($service, callable $validationCallback = null)
    {
        if ($validationCallback && !$validationCallback($service)) {
            return null;
        }

        return $service;
    }
}
