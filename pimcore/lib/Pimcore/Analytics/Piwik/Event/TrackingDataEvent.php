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

namespace Pimcore\Analytics\Piwik\Event;

use Pimcore\Analytics\Code\CodeBlock;
use Pimcore\Analytics\Piwik\Config\Config;
use Pimcore\Analytics\SiteId\SiteId;
use Symfony\Component\EventDispatcher\Event;

class TrackingDataEvent extends Event
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var SiteId
     */
    private $siteId;

    /**
     * @var array
     */
    private $data;

    /**
     * @var CodeBlock[]
     */
    private $blocks;

    /**
     * @var string
     */
    private $template;

    public function __construct(
        Config $config,
        SiteId $siteId,
        array $data,
        array $blocks,
        string $template
    )
    {
        $this->config   = $config;
        $this->siteId   = $siteId;
        $this->data     = $data;
        $this->blocks   = $blocks;
        $this->template = $template;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function getSiteId(): SiteId
    {
        return $this->siteId;
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

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template)
    {
        $this->template = $template;
    }
}
