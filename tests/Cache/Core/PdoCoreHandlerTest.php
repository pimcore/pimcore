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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Tests\Cache\Core;

use Pimcore\Tests\Util\TestHelper;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @group cache.core.db
 */
class PdoCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     * @return PdoAdapter
     */
    protected function createCachePool()
    {
        TestHelper::checkDbSupport();
        $pdoAdapter = new PdoAdapter(\Pimcore::getContainer()->get('doctrine.dbal.default_connection'), '', $this->defaultLifetime);
        $adapter = new TagAwareAdapter($pdoAdapter);

        return $adapter;
    }
}
