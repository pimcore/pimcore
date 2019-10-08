<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Logger;
use Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;

class DefaultValueGeneratorResolver
{
    public static $cache = [];

    /**
     * @param $generatorClass
     *
     * @return DefaultValueGeneratorInterface
     */
    public static function resolveGenerator($generatorClass)
    {
        if ($generatorClass) {
            $generator = null;
            if (substr($generatorClass, 0, 1) === '@') {
                $serviceName = substr($generatorClass, 1);
                try {
                    $generator = \Pimcore::getKernel()->getContainer()->get($serviceName);
                } catch (\Exception $e) {
                    Logger::error($e);
                }
            } else {
                $generator = new $generatorClass;
            }

            if ($generator instanceof DefaultValueGeneratorInterface) {
                return $generator;
            }
        }

        return null;
    }
}