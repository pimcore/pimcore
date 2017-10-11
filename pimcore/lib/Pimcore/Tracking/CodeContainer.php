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

namespace Pimcore\Tracking;

class CodeContainer
{
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

    public function __construct(string $defaultBlock, array $validBlocks)
    {
        if (!in_array($defaultBlock, $validBlocks)) {
            throw new \LogicException(sprintf(
                'The default block "%s" must be a part of the valid blocks',
                $defaultBlock
            ));
        }

        $this->defaultBlock = $defaultBlock;
        $this->validBlocks  = $validBlocks;
    }

    /**
     * Adds additional code to the tracker
     *
     * @param string $configKey
     * @param string $code  The code to add
     * @param string $block The block where to add the code
     * @param bool $prepend Whether to prepend the code to the code block
     */
    public function addCodePart(string $configKey, string $code, string $block = null, bool $prepend = false)
    {
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
     * @param string $configKey
     * @param string $block
     * @param CodeBlock $codeBlock
     */
    public function addToCodeBlock(string $configKey, string $block, CodeBlock $codeBlock)
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
