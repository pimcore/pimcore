<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Element;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;

/**
 * The element types understood by Pimcore\Model\Element\Service.
 */
enum ElementType: string
{
    case Document = 'document';

    case Asset = 'asset';

    case Object = 'object';

    public static function fromElement(ElementInterface $element): ?self
    {
        return match (true) {
            $element instanceof Asset => self::Asset,
            $element instanceof Document => self::Document,
            $element instanceof AbstractObject => self::Object,
            default => null,
        };
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
