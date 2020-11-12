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

namespace Pimcore\Twig\Extension\Templating\Traits;

<<<<<<<< HEAD:lib/Twig/Extension/Templating/Traits/WebLinksTrait.php
use Symfony\Bridge\Twig\Extension\WebLinkExtension;

trait WebLinksTrait
{
    /**
     * @var WebLinkExtension
     */
    protected $webLinkExtension;
========
@trigger_error(
    'Pimcore\Templating\Helper\Traits\WebLinksTrait is deprecated since version 6.8.0 and will be removed in 7.0.0. ' .
    ' Use ' . \Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait::class . ' instead.',
    E_USER_DEPRECATED
);

class_exists(\Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait::class);
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Traits/WebLinksTrait.php

if (false) {
    /**
     * @deprecated since Pimcore 6.8, use Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait
     */
<<<<<<<< HEAD:lib/Twig/Extension/Templating/Traits/WebLinksTrait.php
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

        call_user_func([$this->webLinkExtension, $method], $source, $attributes);
========
    trait WebLinksTrait {
        use \Pimcore\Twig\Extension\Templating\Traits\WebLinksTrait;
>>>>>>>> f48440fd1b... [Templating] ease migration with template helpers (#7463):lib/Templating/Helper/Traits/WebLinksTrait.php
    }
}
