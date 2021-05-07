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

namespace Pimcore\Extension\Bundle\Installer;

/**
 * @deprecated will be removed in Pimcore 10, use OutputInterface instead
 */
interface OutputWriterInterface
{
    /**
     * Writes a message to the output
     *
     * @param string $message The message to write.
     */
    public function write($message);

    /**
     * Returns the written messages
     *
     * @return array
     */
    public function getOutput();
}
