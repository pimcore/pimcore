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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Localizedfield;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

interface CustomResourcePersistingInterface
{
    public function save(Localizedfield|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData|AbstractData|Concrete $object, array $params = []): void;

    public function load(Localizedfield|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData|AbstractData|Concrete $object, array $params = []): mixed;

    public function delete(Localizedfield|\Pimcore\Model\DataObject\Fieldcollection\Data\AbstractData|AbstractData|Concrete $object, array $params = []): void;
}
