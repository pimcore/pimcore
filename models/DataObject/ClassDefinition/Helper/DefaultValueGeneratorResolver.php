<?php

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Model\DataObject\ClassDefinition\DefaultValueGeneratorInterface;

class DefaultValueGeneratorResolver extends ClassResolver
{
    public static function resolveGenerator($generatorClass)
    {
        return self::resolve($generatorClass, static function ($generator) {
            return $generator instanceof DefaultValueGeneratorInterface;
        });
    }
}
