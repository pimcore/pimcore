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

namespace Pimcore\Workflow\Notes;

use Pimcore\Model\Element\ElementInterface;

abstract class AbstractCustomHtmlService implements CustomHtmlServiceInterface
{
    protected string $transitionName = '';

    protected string $actionName = '';

    protected bool $isGlobalAction = false;

    protected string $position;

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

    final public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    final public function getActionName(): string
    {
        return $this->actionName;
    }

    final public function isGlobalAction(): bool
    {
        return $this->isGlobalAction;
    }

    final public function getPosition(): string
    {
        return $this->position;
    }
}
