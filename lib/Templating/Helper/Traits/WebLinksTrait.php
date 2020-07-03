<?php

declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating\Helper\Traits;

use Pimcore\Templating\Helper\WebLink;

trait WebLinksTrait
{
    /**
     * @var WebLink
     */
    protected $webLinkHelper;

    /**
     * Whether to use WebLinks (HTTP/2 push) for every item. Can be
     * overridden on an item level.
     *
     * @var bool
     */
    protected $webLinksEnabled = false;

    public function webLinksEnabled(bool $enabled = null)
    {
        if (null !== $enabled) {
            $this->webLinksEnabled = $enabled;
        }

        return $this->webLinksEnabled;
    }

    public function enableWebLinks(): self
    {
        $this->webLinksEnabled(true);

        return $this;
    }

    public function getWebLinkAttributes(): array
    {
        return $this->webLinkAttributes;
    }

    public function setWebLinkAttributes(array $webLinkAttributes)
    {
        $this->webLinkAttributes = $webLinkAttributes;
    }

    protected function handleWebLink(\stdClass $item, string $source, array $itemAttributes)
    {
        if (empty($source)) {
            return;
        }

        if (!$this->webLinksEnabled && !isset($itemAttributes['webLink'])) {
            return;
        }

        $attributes = $this->webLinkAttributes;
        if (isset($itemAttributes['webLink'])) {
            if (is_bool($itemAttributes['webLink'])) {
                // set webLink to false to disable webLink on the item level. this allows to
                // enable web links for the whole helper while disabling them for individual items
                if (!$itemAttributes['webLink']) {
                    return;
                } else {
                    $itemAttributes['webLink'] = [];
                }
            }

            $attributes = array_merge($attributes, $itemAttributes['webLink']);
        }

        $method = 'preload';
        if (isset($attributes['method'])) {
            $method = $attributes['method'];
            unset($attributes['method']);
        }

        call_user_func([$this->webLinkHelper, $method], $source, $attributes);
    }
}
