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

namespace Pimcore\Bundle\AdminBundle\Event;

use Pimcore\Model\Element\AdminStyle;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ElementAdminStyleEvent extends Event
{
    /**
     * Style needed for tree
     */
    const CONTEXT_TREE = 1;

    /**
     * Style needed for element editor
     */
    const CONTEXT_EDITOR = 2;

    /**
     * Style needed for quicksearch
     */
    const CONTEXT_SEARCH = 3;

    protected ?int $context = null;

    protected ElementInterface $element;

    protected AdminStyle $adminStyle;

    /**
     * ElementAdminStyleEvent constructor.
     *
     * @param ElementInterface $element
     * @param AdminStyle $adminStyle
     * @param int|null $context
     */
    public function __construct(ElementInterface $element, AdminStyle $adminStyle, int $context = null)
    {
        $this->element = $element;
        $this->adminStyle = $adminStyle;
        $this->context = $context;
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    public function setElement(ElementInterface $element): void
    {
        $this->element = $element;
    }

    public function getAdminStyle(): AdminStyle
    {
        return $this->adminStyle;
    }

    public function setAdminStyle(AdminStyle $adminStyle): void
    {
        $this->adminStyle = $adminStyle;
    }

    /**
     * Returns the context. e.g. CONTEXT_TREE or CONTEXT_EDITOR.
     *
     * @return null|int
     */
    public function getContext(): ?int
    {
        return $this->context;
    }

    public function setContext(?int $context): void
    {
        $this->context = $context;
    }
}
