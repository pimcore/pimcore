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

namespace Pimcore\Model\Dao;

use Pimcore\Db\PimcoreConfigStorage;

/**
 * @internal
 */
abstract class PimcoreConfigBag implements DaoInterface
{
    use DaoTrait;

    /**
     * @var array
     */
    protected $db;

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        // nothing to do
    }

    /**
     * @param string $legacyKey
     */
    protected function setContext($legacyKey)
    {
        $key = str_replace("-", "", $legacyKey);
        $container = \Pimcore::getContainer();
        $config = $container->getParameter("pimcore.config");
        $theConfig = $config[$key];

        $this->db = PimcoreConfigStorage::get($key, $legacyKey);
    }
}
