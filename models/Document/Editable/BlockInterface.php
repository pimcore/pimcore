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

namespace Pimcore\Model\Document\Editable;

interface BlockInterface
{
    /**
     * Loops through the block
     *
     * @return bool
     */
    public function loop();

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return $this
     */
    public function start();

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     */
    public function end();

    /**
     * Called before the block is rendered
     */
    public function blockConstruct();

    /**
     * Called when the block was rendered
     */
    public function blockDestruct();

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     */
    public function blockStart();

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     */
    public function blockEnd();

    /**
     * Return the amount of block elements
     *
     * @return int
     */
    public function getCount();

    /**
     * Return current iteration step
     *
     * @return int
     */
    public function getCurrent();

    /**
     * Return current index
     *
     * @return int
     */
    public function getCurrentIndex();

    /**
     * @return bool
     */
    public function isEmpty();
}

class_alias(BlockInterface::class, 'Pimcore\Model\Document\Tag\BlockInterface');
