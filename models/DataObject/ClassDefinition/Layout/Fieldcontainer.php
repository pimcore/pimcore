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
use Pimcore\Model\DataObject\ClassDefinition\Layout\Traits\LabelTrait;

class Fieldcontainer extends Model\DataObject\ClassDefinition\Layout
{
    use LabelTrait;

    /**
     * Static type of this element
     *
     * @internal
     */
    public string $fieldtype = 'fieldcontainer';

    /**
     * @internal
     */
    public string $layout = 'hbox';

    /**
     * @internal
     */
    public string $fieldLabel;

    /**
     * @return $this
     */
    public function setLayout(string $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @return $this
     */
    public function setFieldLabel(string $fieldLabel): static
    {
        $this->fieldLabel = $fieldLabel;

        return $this;
    }

    public function getFieldLabel(): string
    {
        return $this->fieldLabel;
    }
}
