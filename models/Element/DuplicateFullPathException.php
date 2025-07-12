<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
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
