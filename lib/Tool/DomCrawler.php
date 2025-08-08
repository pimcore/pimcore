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

namespace Pimcore\Tool;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @internal
 */
class DomCrawler extends Crawler
{
    public const FRAGMENT_WRAPPER_TAG = 'pimcore-fragment-wrapper';

    private bool $wrappedHtmlFragment = false;

    public function __construct($node = null, ?string $uri = null, ?string $baseHref = null)
    {
        if (is_string($node)) {
            // check if given node is an HTML fragment, if so wrap it in a custom tag, otherwise
            // DomDocument wraps standalone text-nodes (without a parent node) into <p> tags
            if (!preg_match('@</(body|html)>@i', $node)) {
                $node = sprintf('<!doctype html><html><%s>%s</%s></html>', self::FRAGMENT_WRAPPER_TAG, $node, self::FRAGMENT_WRAPPER_TAG);
                $this->wrappedHtmlFragment = true;
            }
        }

        parent::__construct($node, $uri, $baseHref);
    }

    public function html(?string $default = null): string
    {
        if ($this->wrappedHtmlFragment) {
            $html = $this->filter(self::FRAGMENT_WRAPPER_TAG)->html();
        } else {
            $html = parent::html($default);
        }

        return $html;
    }
}
