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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Messenger\Handler;

use Pimcore\Bundle\DataImporterBundle\Preview\Model\PreviewData;
use Pimcore\Bundle\DataImporterBundle\Resolver\Resolver;
use Pimcore\Bundle\DataImporterBundle\Settings\SettingsAwareInterface;

interface MaintenanceTaskHandlerInterface
{
    /**
     * Prevents the handler from serving message based on the excluded flag
     *
     * @return bool
     */
    public function isExcluded(): bool;


    /**
     * @param bool $exclude
     *
     */
    public function setExcluded(bool $exclude): void;
}
