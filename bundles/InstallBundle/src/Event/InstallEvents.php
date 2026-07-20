<?php

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\Event;

class InstallEvents
{
    /**
     * Event gets fire for every installer step e.g. install assets, install db
     */
    public const EVENT_NAME_STEP = 'pimcore.installer.step';

    /**
     * Event is fired before bundle selection in installer. Bundles and Recommendations can be added or removed here
     */
    public const EVENT_BUNDLE_SETUP = 'pimcore.installer.setup_bundles';
}
