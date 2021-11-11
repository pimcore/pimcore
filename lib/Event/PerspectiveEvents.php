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

namespace Pimcore\Event;

final class PerspectiveEvents
{
    /**
     * This event is fired after Pimcore generates the runtime Perspective
     *
     * Arguments:
     *  - result | The result array
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const POST_GET_RUNTIME_PERSPECTIVE = 'pimcore.perspective.postGetRuntimePerspective';
}
