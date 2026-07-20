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

interface QueryResourcePersistenceAwareInterface
{
    /**
     * Returns the data which should be stored in the query columns
     *
     *
     */
    public function getDataForQueryResource(mixed $data, ?Concrete $object = null, array $params = []): mixed;

    public function getQueryColumnType(): array|string;
}
