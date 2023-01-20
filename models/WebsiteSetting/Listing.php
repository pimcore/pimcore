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

namespace Pimcore\Model\WebsiteSetting;

use Pimcore\Model;
use Pimcore\Model\WebsiteSetting;

/**
 * @method WebsiteSetting\Listing\Dao getDao()
 * @method WebsiteSetting[] load()
 * @method int getTotalCount()
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @internal
     *
     * @var WebsiteSetting[]|null
     */
    protected ?array $settings = null;

    /**
     * @param WebsiteSetting[]|null $settings
     */
    public function setSettings(?array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return WebsiteSetting[]
     */
    public function getSettings(): array
    {
        if ($this->settings === null) {
            $this->getDao()->load();
        }

        return $this->settings;
    }
}
