<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Helper;

use Pimcore\Model\DataObject\ClassDefinition\LinkGeneratorInterface;

class LinkGeneratorResolver extends ClassResolver
{
    /**
     * @param string $generatorClass
     *
     * @return LinkGeneratorInterface|null
     */
    public static function resolveGenerator($generatorClass)
    {
        return self::resolve($generatorClass, static function ($generator) {
            return $generator instanceof LinkGeneratorInterface;
        });
    }
}
