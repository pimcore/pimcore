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

namespace Pimcore\Tests\Cache\Core;

use Pimcore;
use Pimcore\Tests\Support\Util\TestHelper;
use Symfony\Component\Cache\Adapter\DoctrineDbalAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @group cache.core.db
 */
class DoctrineDbalCoreHandlerTest extends AbstractCoreHandlerTest
{
    /**
     * Initializes item pool
     *
     */
    protected function createCachePool(): TagAwareAdapter
    {
        TestHelper::checkDbSupport();
        $doctrineDbalAdapter = new DoctrineDbalAdapter(Pimcore::getContainer()->get('doctrine.dbal.default_connection'), '', $this->defaultLifetime);
        $adapter = new TagAwareAdapter($doctrineDbalAdapter);

        return $adapter;
    }
}
