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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

interface ModelInterface
{
    /**
     * @return Dao\AbstractDao
     */
    public function getDao();

    /**
     * @param Dao\AbstractDao $dao
     *
     * @return self
     */
    public function setDao($dao);

    /**
     * @param string|null $key
     * @param bool $forceDetection
     *
     * @throws \Exception
     */
    public function initDao($key = null, $forceDetection = false);

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setValues($data = []);

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setValue($key, $value);
}
