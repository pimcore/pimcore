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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

interface DataDefinitionInterface
{
    /**
     * @param mixed $data
     * @param array $params
     *
     * @return bool
     */
    public function isEmpty(mixed $data, array $params = []): bool;

    /**
     * @param mixed $data
     * @param array $params
     *
     * @throws \Exception
     */
    public function checkValidity(mixed $data, array $params = []);

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataForListfolderGrid(mixed $data, array $params = []): mixed;

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromEditMode(mixed $data, array $params = []): mixed;

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromListfolderGrid(mixed $data, array $params = []): mixed;

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return array
     */
    public function resolveDependencies(mixed $data, array $params = []): array;
}
