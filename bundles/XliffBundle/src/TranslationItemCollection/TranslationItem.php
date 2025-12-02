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

namespace Pimcore\Bundle\XliffBundle\TranslationItemCollection;

use Pimcore\Model\Element\ElementInterface;

class TranslationItem
{
    private string $type;

    private string $id;

    private ElementInterface $element;

    /**
     * TranslationItem constructor.
     *
     */
    public function __construct(string $type, string $id, ElementInterface $element)
    {
        $this->type = $type;
        $this->id = $id;
        $this->element = $element;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getElement(): ElementInterface
    {
        return $this->element;
    }
}
