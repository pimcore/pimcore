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
