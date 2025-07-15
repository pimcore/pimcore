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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

use Exception;

interface DataDefinitionInterface
{
    public function isEmpty(mixed $data, array $params = []): bool;

    /**
     *
     * @throws Exception
     */
    public function checkValidity(mixed $data, array $params = []): void;

    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed;

    public function getDataFromEditMode(mixed $data, array $params = []): mixed;

    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed;

    public function resolveDependencies(mixed $data, array $params = []): array;
}
