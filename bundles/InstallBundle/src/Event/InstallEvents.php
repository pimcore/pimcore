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
