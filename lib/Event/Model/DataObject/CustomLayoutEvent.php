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

namespace Pimcore\Event\Model\DataObject;

use Pimcore\Model\DataObject\ClassDefinition;
use Symfony\Contracts\EventDispatcher\Event;

class CustomLayoutEvent extends Event
{
    protected ClassDefinition\CustomLayout $customLayout;

    /**
     * DocumentEvent constructor.
     *
     */
    public function __construct(ClassDefinition\CustomLayout $customLayout)
    {
        $this->customLayout = $customLayout;
    }

    public function getCustomLayout(): ClassDefinition\CustomLayout
    {
        return $this->customLayout;
    }

    public function setCustomLayout(ClassDefinition\CustomLayout $customLayout): void
    {
        $this->customLayout = $customLayout;
    }
}
