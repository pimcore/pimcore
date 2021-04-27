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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Workflow\Notes;

use Pimcore\Model\Element\ElementInterface;

abstract class AbstractCustomHtmlService implements CustomHtmlServiceInterface
{
    protected $transitionName = '';

    protected $actionName = '';

    protected $isGlobalAction = false;

    protected $position;

    public function __construct(string $actionOrTransitionName, bool $isGlobalAction, string $position = '')
    {
        $this->actionName = $isGlobalAction ? $actionOrTransitionName : '';
        $this->transitionName = !$isGlobalAction ? $actionOrTransitionName : '';
        $this->isGlobalAction = $isGlobalAction;
        $this->position = $position;
    }

    public function renderHtmlForRequestedPosition(ElementInterface $element, string $requestedPosition): string
    {
        if ($this->getPosition() === $requestedPosition) {
            return $this->renderHtml($element);
        }

        return '';
    }

    /**
     * @return string
     */
    final public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    /**
     * @return string
     */
    final public function getActionName(): string
    {
        return $this->actionName;
    }

    /**
     * @return bool
     */
    final public function isGlobalAction(): bool
    {
        return $this->isGlobalAction;
    }

    /**
     * @return string
     */
    final public function getPosition(): string
    {
        return $this->position;
    }
}
