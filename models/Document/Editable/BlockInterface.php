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

namespace Pimcore\Model\Document\Editable;

use Generator;

interface BlockInterface
{
    public function getIterator(): Generator;

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return void|string|$this
     */
    public function start();

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     *
     * @return void|string
     */
    public function end();

    /**
     * Called before the block is rendered
     */
    public function blockConstruct(): void;

    /**
     * Called when the block was rendered
     */
    public function blockDestruct(): void;

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @return void|string|array
     */
    public function blockStart();

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     *
     * @return void|string
     */
    public function blockEnd();

    /**
     * Return the amount of block elements
     *
     */
    public function getCount(): int;

    /**
     * Return current iteration step
     *
     */
    public function getCurrent(): int;

    /**
     * Return current index
     *
     */
    public function getCurrentIndex(): int;

    public function isEmpty(): bool;
}
