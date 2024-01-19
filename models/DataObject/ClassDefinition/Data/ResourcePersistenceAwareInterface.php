<?php
declare(strict_types=1);

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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;

interface ResourcePersistenceAwareInterface
{
    /**
     * Returns the the data that should be stored in the resource
     *
     *
     */
    public function getDataForResource(mixed $data, Concrete $object = null, array $params = []): mixed;

    /**
     * Convert the saved data in the resource to the internal eg. Image-Id to Asset\Image object, this is the inverted getDataForResource()
     *
     *
     */
    public function getDataFromResource(mixed $data, Concrete $object = null, array $params = []): mixed;

    public function getColumnType(): array|string;
}
