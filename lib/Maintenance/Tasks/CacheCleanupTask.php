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

namespace Pimcore\Maintenance\Tasks;

use Pimcore\Cache\Core\CoreHandlerInterface;
use Pimcore\Maintenance\TaskInterface;

final class CacheCleanupTask implements TaskInterface
{
    /**
     * @var CoreHandlerInterface
     */
    private $cacheHandler;

    /**
     * CacheCleanupTask constructor.
     *
     * @param CoreHandlerInterface $cacheHandler
     */
    public function __construct(CoreHandlerInterface $cacheHandler)
    {
        $this->cacheHandler = $cacheHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $this->cacheHandler->purge();
    }
}
