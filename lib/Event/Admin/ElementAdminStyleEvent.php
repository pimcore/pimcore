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

namespace Pimcore\Event\Admin;

use Pimcore\Model\Element\AdminStyle;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\EventDispatcher\Event;

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
     * @var int
     */
    protected $context;

    /**
     * @var ElementInterface
     */
    protected $element;

    /**
     * @var AdminStyle
     */
    protected $adminStyle;

    /**
     * ElementAdminStyleEvent constructor.
     *
     * @param ElementInterface $element
     * @param AdminStyle $adminStyle
     * @param null|int $context
     */
    public function __construct(ElementInterface $element, AdminStyle $adminStyle, $context = null)
    {
        $this->element = $element;
        $this->adminStyle = $adminStyle;
        $this->context = $context;
    }

    /**
     * @return ElementInterface
     */
    public function getElement(): ElementInterface
    {
        return $this->element;
    }

    /**
     * @param ElementInterface $element
     */
    public function setElement(ElementInterface $element): void
    {
        $this->element = $element;
    }

    /**
     * @return AdminStyle
     */
    public function getAdminStyle(): AdminStyle
    {
        return $this->adminStyle;
    }

    /**
     * @param AdminStyle $adminStyle
     */
    public function setAdminStyle(AdminStyle $adminStyle): void
    {
        $this->adminStyle = $adminStyle;
    }

    /**
     * Returns the context. CONTEXT_TREE or CONTEXT_EDITOR.
     *
     * @return null|int
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param null|int $context
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }
}
