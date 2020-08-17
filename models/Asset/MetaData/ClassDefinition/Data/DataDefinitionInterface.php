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

namespace Pimcore\Model\Asset\MetaData\ClassDefinition\Data;

interface DataDefinitionInterface
{
    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function isEmpty($data, $params = []);

    /**
     * @param mixed $data
     * @param array $params
     * throws \Exception
     */
    public function checkValidity($data, $params = []);

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataForListfolderGrid($data, $params = []);

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromEditMode($data, $params = []);

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return mixed
     */
    public function getDataFromListfolderGrid($data, $params = []);

    /**
     * @param mixed $data
     * @param array $params
     *
     * @return array
     */
    public function resolveDependencies($data, $params = []);
}
