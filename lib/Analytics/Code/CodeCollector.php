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

use Pimcore\Analytics\SiteId\SiteId;

/**
 * Collects additional code parts which should be added to specific blocks upon rendering. Code
 * parts can be added on a global level or restricted to a specific site.
 */
class CodeCollector
{
    const CONFIG_KEY_GLOBAL = '__global';

    const ACTION_PREPEND = 'prepend';
    const ACTION_APPEND = 'append';

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

    /**
     * @var array
     */
    private $validActions = [
        self::ACTION_PREPEND,
        self::ACTION_APPEND,
    ];

    public function __construct(array $validBlocks, string $defaultBlock)
    {
        if (!in_array($defaultBlock, $validBlocks)) {
            throw new \LogicException(sprintf(
                'The default block "%s" must be a part of the valid blocks',
                $defaultBlock
            ));
        }

        $this->validBlocks = $validBlocks;
        $this->defaultBlock = $defaultBlock;
    }

    /**
     * Adds additional code to the tracker
     *
     * @param string $code
     * @param string|null $block
     * @param string $action
     * @param SiteId|null $siteId Restrict code part to a specific site
     */
    public function addCodePart(string $code, string $block = null, string $action = self::ACTION_APPEND, SiteId $siteId = null)
    {
        if (!in_array($action, $this->validActions)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid action "%s". Valid actions are: %s',
                $action,
                implode(', ', $this->validActions)
            ));
        }

        $configKey = self::CONFIG_KEY_GLOBAL;
        if (null !== $siteId) {
            $configKey = $siteId->getConfigKey();
        }

        if (null === $block) {
            $block = $this->defaultBlock;
        }

        if (!in_array($block, $this->validBlocks)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid block "%s". Valid values are: %s',
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

        if (!isset($this->codeParts[$configKey][$block][$action])) {
            $this->codeParts[$configKey][$block][$action] = [];
        }

        $this->codeParts[$configKey][$block][$action][] = $code;
    }

    /**
     * Adds registered parts to a code block
     *
     * @param SiteId $siteId
     * @param CodeBlock $codeBlock
     * @param string $block
     */
    public function enrichCodeBlock(SiteId $siteId, CodeBlock $codeBlock, string $block)
    {
        // global parts not restricted to a config key
        $this->enrichBlock(self::CONFIG_KEY_GLOBAL, $codeBlock, $block);

        // config key specific parts
        $this->enrichBlock($siteId->getConfigKey(), $codeBlock, $block);
    }

    private function enrichBlock(string $configKey, CodeBlock $codeBlock, string $block)
    {
        if (!isset($this->codeParts[$configKey])) {
            return;
        }

        $blockParts = $this->codeParts[$configKey][$block] ?? [];
        if (empty($blockParts)) {
            return;
        }

        foreach ([self::ACTION_PREPEND, self::ACTION_APPEND] as $position) {
            if (isset($blockParts[$position])) {
                if (self::ACTION_PREPEND === $position) {
                    $codeBlock->prepend($blockParts[$position]);
                } else {
                    $codeBlock->append($blockParts[$position]);
                }
            }
        }
    }
}
