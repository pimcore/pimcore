<?php
declare(strict_types=1);

namespace Pimcore\Extension\Document\Areabrick\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAreabrick
{
    public function __construct(
        public readonly ?string $id = null,
    ) {
    }
}
