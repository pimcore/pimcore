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

namespace Pimcore\Event;

final class CoreCacheEvents
{
    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     *
     * @var string
     */
    const INIT = 'pimcore.cache.core.init';

    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     *
     * @var string
     */
    const ENABLE = 'pimcore.cache.core.enable';

    /**
     * @Event("Symfony\Component\EventDispatcher\Event")
     *
     * @var string
     */
    const DISABLE = 'pimcore.cache.core.disable';

    /**
     * @Event("Pimcore\Event\Cache\Core\ResultEvent")
     *
     * @var string
     */
    const PURGE = 'pimcore.cache.core.purge';
}
