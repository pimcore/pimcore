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

namespace Pimcore\Templating\Helper;

use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\Templating\Helper\Helper;

/**
 * @deprecated
 */
class WebLink extends Helper
{
    /**
     * @var WebLinkExtension
     */
    private $webLinkExtension;

    public function __construct(WebLinkExtension $webLinkExtension)
    {
        $this->webLinkExtension = $webLinkExtension;
    }

    public function getName()
    {
        return 'webLink';
    }

    /**
     * Adds a "Link" HTTP header.
     *
     * @param string $uri       The relation URI
     * @param string $rel       The relation type (e.g. "preload", "prefetch", "prerender" or "dns-prefetch")
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('pr' => 0.5)")
     *
     * @return string The relation URI
     */
    public function link($uri, $rel, array $attributes = [])
    {
        return $this->webLinkExtension->link($uri, $rel, $attributes);
    }

    /**
     * Preloads a resource.
     *
     * @param string $uri       A public path
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('crossorigin' =>
     *                          'use-credentials')")
     *
     * @return string The path of the asset
     */
    public function preload($uri, array $attributes = [])
    {
        return $this->webLinkExtension->preload($uri, $attributes);
    }

    /**
     * Resolves a resource origin as early as possible.
     *
     * @param string $uri       A public path
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('pr' => 0.5)")
     *
     * @return string The path of the asset
     */
    public function dnsPrefetch($uri, array $attributes = [])
    {
        return $this->webLinkExtension->dnsPrefetch($uri, $attributes);
    }

    /**
     * Initiates a early connection to a resource (DNS resolution, TCP handshake, TLS negotiation).
     *
     * @param string $uri       A public path
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('pr' => 0.5)")
     *
     * @return string The path of the asset
     */
    public function preconnect($uri, array $attributes = [])
    {
        return $this->webLinkExtension->preconnect($uri, $attributes);
    }

    /**
     * Indicates to the client that it should prefetch this resource.
     *
     * @param string $uri       A public path
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('pr' => 0.5)")
     *
     * @return string The path of the asset
     */
    public function prefetch($uri, array $attributes = [])
    {
        return $this->webLinkExtension->prefetch($uri, $attributes);
    }

    /**
     * Indicates to the client that it should prerender this resource .
     *
     * @param string $uri       A public path
     * @param array $attributes The attributes of this link (e.g. "array('as' => true)", "array('pr' => 0.5)")
     *
     * @return string The path of the asset
     */
    public function prerender($uri, array $attributes = [])
    {
        return $this->webLinkExtension->prerender($uri, $attributes);
    }
}
