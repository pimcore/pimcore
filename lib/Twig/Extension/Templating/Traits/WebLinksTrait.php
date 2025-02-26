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

namespace Pimcore\Twig\Extension\Templating\Traits;

use stdClass;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;

/**
 * @internal
 */
trait WebLinksTrait
{
    protected WebLinkExtension $webLinkExtension;

    /**
     * Whether to use WebLinks (HTTP/2 push) for every item. Can be
     * overridden on an item level.
     *
     */
    protected bool $webLinksEnabled = false;

    public function webLinksEnabled(?bool $enabled = null): bool
    {
        if (null !== $enabled) {
            $this->webLinksEnabled = $enabled;
        }

        return $this->webLinksEnabled;
    }

    public function enableWebLinks(): static
    {
        $this->webLinksEnabled(true);

        return $this;
    }

    public function getWebLinkAttributes(): array
    {
        return $this->webLinkAttributes;
    }

    public function setWebLinkAttributes(array $webLinkAttributes): void
    {
        $this->webLinkAttributes = $webLinkAttributes;
    }

    protected function handleWebLink(stdClass $item, string $source, array $itemAttributes): void
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

        call_user_func([$this->webLinkExtension, $method], $source, $attributes);
    }
}
