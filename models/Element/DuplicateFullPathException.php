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

namespace Pimcore\Model\Element;

use Exception;

class DuplicateFullPathException extends Exception
{
    private ?ElementInterface $causeElement = null;

    private ?ElementInterface $duplicateElement = null;

    public function setDuplicateElement(?ElementInterface $duplicateElement): void
    {
        $this->duplicateElement = $duplicateElement;
    }

    public function getDuplicateElement(): ?ElementInterface
    {
        return $this->duplicateElement;
    }

    public function setCauseElement(?ElementInterface $causeElement): void
    {
        $this->causeElement = $causeElement;
    }

    public function getCauseElement(): ?ElementInterface
    {
        return $this->causeElement;
    }
}
