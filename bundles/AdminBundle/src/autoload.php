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

/**
 * TODO: BC layer, remove with Pimcore 12
 */
$classAliases = [
    '\Pimcore\Bundle\AdminBundle\Event\AdminEvents' => '\Pimcore\Event\AdminEvents',
    '\Pimcore\Bundle\AdminBundle\Event\IndexActionSettingsEvent\AdminListener' => '\Pimcore\Event\Admin\IndexActionSettingsEvent\AdminListener',
    '\Pimcore\Bundle\AdminBundle\Event\Login\LogoutEvent' => '\Pimcore\Event\Admin\Login\LogoutEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Login\LostPasswordEvent' => '\Pimcore\Event\Admin\Login\LostPasswordEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Login\LoginRedirectEvent' => '\Pimcore\Event\Admin\Login\LoginRedirectEvent',
    '\Pimcore\Bundle\AdminBundle\Event\ElementAdminStyleEvent' => '\Pimcore\Event\Admin\ElementAdminStyleEvent',
    '\Pimcore\Bundle\AdminBundle\Event\IndexActionSettingsEvent' => '\Pimcore\Event\Admin\IndexActionSettingsEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Model\AssetDeleteInfoEvent' => '\Pimcore\Event\Model\AssetDeleteInfoEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Model\DataObjectDeleteInfoEvent' => '\Pimcore\Event\Model\DataObjectDeleteInfoEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Model\DocumentDeleteInfoEvent' => '\Pimcore\Event\Model\DocumentDeleteInfoEvent',
    '\Pimcore\Bundle\AdminBundle\Event\Model\ElementDeleteInfoEventInterface' => '\Pimcore\Event\Model\ElementDeleteInfoEventInterface',

];

foreach ($classAliases as $class => $alias) {
    if (!class_exists($alias, false)) {
        @class_alias($class, $alias);
    }
}
