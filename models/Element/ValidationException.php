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

class ValidationException extends Exception
{
    protected array $contextStack = [];

    /** @var Exception[] */
    protected array $subItems = [];

    /**
     * @return Exception[]
     */
    public function getSubItems(): array
    {
        return $this->subItems;
    }

    /**
     * @param Exception[] $subItems
     */
    public function setSubItems(array $subItems = []): void
    {
        $this->subItems = $subItems;
    }

    public function addContext(string $context): void
    {
        $this->contextStack[] = $context;
    }

    public function getContextStack(): array
    {
        return $this->contextStack;
    }

    public function __toString(): string
    {
        $result = parent::__toString();
        foreach ($this->subItems as $subItem) {
            $result .= "\n\n";
            $result .= $subItem->__toString();
        }

        return $result;
    }

    public function getAggregatedMessage(): string
    {
        $msg = $this->getMessage();
        $contextStack = $this->getContextStack();
        if ($contextStack) {
            $msg .= '[ '.$contextStack[0].' ]';
        }

        $subItems = $this->getSubItems();
        if (count($subItems) > 0) {
            $msg .= ' (';
            $subItemParts = [];

            foreach ($subItems as $subItem) {
                if ($subItem instanceof self) {
                    $subItemMessage = $subItem->getAggregatedMessage();
                    $contextStack = $subItem->getContextStack();
                    if ($contextStack) {
                        $subItemMessage .= '[ '.$contextStack[0].' ]';
                    }
                } else {
                    $subItemMessage = $subItem->getMessage();
                }
                $subItemParts[] = $subItemMessage;
            }
            $msg .= implode(', ', $subItemParts);
            $msg .= ')';
        }

        return $msg;
    }
}
