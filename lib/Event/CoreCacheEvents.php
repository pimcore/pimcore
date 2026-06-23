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
