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

namespace Pimcore\Analytics\Code;

use Pimcore\Analytics\SiteConfig\SiteConfig;

/**
 * Collects additional code parts which should be added to specific blocks upon rendering. Code
 * parts can be added on a global level or restricted to a specific site.
 */
class CodeContainer
{
    const CONFIG_KEY_GLOBAL = '__global';
    const POSITION_PREPEND = 'prepend';
    const POSITION_APPEND = 'append';

    /**
     * @var string
     */
    private $defaultBlock;

    /**
     * @var array
     */
    private $validBlocks;

    /**
     * @var array
     */
    private $codeParts = [];

    public function __construct(array $validBlocks, string $defaultBlock)
    {
        if (!in_array($defaultBlock, $validBlocks)) {
            throw new \LogicException(sprintf(
                'The default block "%s" must be a part of the valid blocks',
                $defaultBlock
            ));
        }

        $this->validBlocks  = $validBlocks;
        $this->defaultBlock = $defaultBlock;
    }

    /**
     * Adds additional code to the tracker
     *
     * @param string $code
     * @param string|null $block
     * @param bool $prepend
     * @param SiteConfig|null $siteConfig Restrict code part to a specific site
     */
    public function addCodePart(string $code, string $block = null, bool $prepend = false, SiteConfig $siteConfig = null)
    {
        $configKey = self::CONFIG_KEY_GLOBAL;
        if (null !== $siteConfig) {
            $configKey = $siteConfig->getConfigKey();
        }

        if (null === $block) {
            $block = $this->defaultBlock;
        }

        if (!in_array($block, $this->validBlocks)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid block "%s". Valid values are %s',
                $block,
                implode(', ', $this->validBlocks)
            ));
        }

        if (!isset($this->codeParts[$configKey])) {
            $this->codeParts[$configKey] = [];
        }

        if (!isset($this->codeParts[$configKey][$block])) {
            $this->codeParts[$configKey][$block] = [];
        }

        $position = $prepend ? self::POSITION_PREPEND : self::POSITION_APPEND;
        if (!isset($this->codeParts[$configKey][$block][$position])) {
            $this->codeParts[$configKey][$block][$position] = [];
        }

        $this->codeParts[$configKey][$block][$position][] = $code;
    }

    /**
     * Adds registered parts to a code block
     *
     * @param SiteConfig $siteConfig
     * @param CodeBlock $codeBlock
     * @param string $block
     */
    public function addToCodeBlock(SiteConfig $siteConfig, CodeBlock $codeBlock, string $block)
    {
        // global parts not restricted to a config key
        $this->enrichBlock(self::CONFIG_KEY_GLOBAL, $codeBlock, $block);

        // config key specific parts
        $this->enrichBlock($siteConfig->getConfigKey(), $codeBlock, $block);
    }

    private function enrichBlock(string $configKey, CodeBlock $codeBlock, string $block)
    {
        if (!isset($this->codeParts[$configKey])) {
            return;
        }

        $blockCalls = $this->codeParts[$configKey][$block] ?? [];
        if (empty($blockCalls)) {
            return;
        }

        foreach ([self::POSITION_PREPEND, self::POSITION_APPEND] as $position) {
            if (isset($blockCalls[$position])) {
                if (self::POSITION_PREPEND === $position) {
                    $codeBlock->prepend($blockCalls[$position]);
                } else {
                    $codeBlock->append($blockCalls[$position]);
                }
            }
        }
    }
}
