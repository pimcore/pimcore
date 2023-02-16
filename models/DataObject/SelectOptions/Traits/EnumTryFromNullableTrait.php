<?php
declare(strict_types=1);

namespace Pimcore\Model\DataObject\SelectOptions\Traits;

trait EnumTryFromNullableTrait
{
    public static function tryFromNullable(?string $value): ?static
    {
        if ($value === null) {
            return null;
        }
        return static::tryFrom($value);
    }
}
