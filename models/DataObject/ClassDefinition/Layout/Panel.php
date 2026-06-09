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

namespace Pimcore\Model\DataObject\ClassDefinition\Layout;

use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Traits\IconTrait;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Traits\LabelTrait;

class Panel extends Model\DataObject\ClassDefinition\Layout
{
    use IconTrait;
    use LabelTrait;

    /**
     * Static type of this element
     *
     * @internal
     */
    public string $fieldtype = 'panel';

    /**
     * @internal
     */
    public ?string $layout = null;

    /**
     * @internal
     */
    public bool $border = false;

    /**
     * @return $this
     */
    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): ?string
    {
        return $this->layout;
    }

    public function getBorder(): bool
    {
        return $this->border;
    }

    public function setBorder(bool $border): void
    {
        $this->border = $border;
    }
}
