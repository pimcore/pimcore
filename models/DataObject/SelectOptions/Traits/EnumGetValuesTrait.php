<?php
declare(strict_types=1);

namespace Pimcore\Model\DataObject\SelectOptions\Traits;

trait EnumGetValuesTrait
{
    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return array_column(static::cases(), 'value');
    }
}
