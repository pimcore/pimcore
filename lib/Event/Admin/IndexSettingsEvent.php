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

namespace Pimcore\Event\Admin;

use Pimcore\Templating\Model\ViewModel;
use Symfony\Component\EventDispatcher\Event;

/**
 * @deprecated will be removed in Pimcore 10, use IndexActionSettingsEvent instead
 */
class IndexSettingsEvent extends Event
{
    /**
     * @var ViewModel
     */
    private $settings;

    public function __construct(ViewModel $settings)
    {
        $this->settings = $settings;
    }

    public function getSettings(): ViewModel
    {
        return $this->settings;
    }
}
