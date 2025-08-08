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
