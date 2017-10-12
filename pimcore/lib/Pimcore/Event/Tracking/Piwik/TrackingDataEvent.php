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

namespace Pimcore\Event\Tracking\Piwik;

use Pimcore\Config\Config;
use Pimcore\Tracking\Code\CodeBlock;
use Pimcore\Tracking\SiteConfig\SiteConfig;
use Symfony\Component\EventDispatcher\Event;

class TrackingDataEvent extends Event
{
    /**
     * @var SiteConfig
     */
    private $siteConfig;

    /**
     * @var array
     */
    private $data;

    /**
     * @var CodeBlock[]
     */
    private $blocks;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Config
     */
    private $trackerConfig;

    /**
     * @var string
     */
    private $template;

    public function __construct(
        SiteConfig $siteConfig,
        array $data,
        array $blocks,
        Config $config,
        Config $trackerConfig,
        string $template
    )
    {
        $this->siteConfig    = $siteConfig;
        $this->data          = $data;
        $this->blocks        = $blocks;
        $this->config        = $config;
        $this->trackerConfig = $trackerConfig;
        $this->template      = $template;
    }

    public function getSiteConfig(): SiteConfig
    {
        return $this->siteConfig;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * @return CodeBlock[]
     */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function getBlock(string $block): CodeBlock
    {
        if (!isset($this->blocks[$block])) {
            throw new \InvalidArgumentException(sprintf('Invalid block "%s"', $block));
        }

        return $this->blocks[$block];
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getTrackerConfig(): Config
    {
        return $this->trackerConfig;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }
}
