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

namespace Pimcore\Event;

final class CoreCacheEvents
{
    /**
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const INIT = 'pimcore.cache.core.init';

    /**
     * @Event("Symfony\Contracts\EventDispatcher\Event")
     *
     * @var string
     */
    const ENABLE = 'pimcore.cache.core.enable';

    /**
     * @Event("Symfony\Contracts\EventDispatcher\Event")
     *
     * @var string
     */
    const DISABLE = 'pimcore.cache.core.disable';
}
