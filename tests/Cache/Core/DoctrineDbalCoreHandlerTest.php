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
