<?php

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

namespace Pimcore\Model\Exception;

use Pimcore\Model\Element\AbstractElement;

class DuplicateFullPathException extends \Exception
{
    private AbstractElement $duplicateElement;

    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null, AbstractElement $duplicateElement = null)
    {
        $this->duplicateElement = $duplicateElement;

        parent::__construct($message, $code, $previous);
    }

    public function getDuplicateElement(): ?AbstractElement
    {
        return $this->duplicateElement;
    }
}
