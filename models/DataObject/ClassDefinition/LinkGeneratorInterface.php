<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition;

use Pimcore\Model\DataObject\Concrete;

interface LinkGeneratorInterface
{
    /**
     *
     * If you want to support mockups or arbitrary objects you can change the typehint to
     * public function generate(object $object, array $params = []): string
     *
     * TODO: Change type of `$object` parameter to `object` in Pimcore 11
     *
     * @param Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function generate(Concrete $object, array $params = []): string;
}
