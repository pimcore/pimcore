<?php
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

namespace Pimcore\Translation\TranslationItemCollection;

use Pimcore\Model\Element\ElementInterface;

class TranslationItem
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @var ElementInterface
     */
    private $element;

    /**
     * TranslationItem constructor.
     *
     * @param string $type
     * @param string $id
     * @param ElementInterface $element
     */
    public function __construct(string $type, string $id, $element)
    {
        $this->type = $type;
        $this->id = $id;
        $this->element = $element;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ElementInterface
     */
    public function getElement()
    {
        return $this->element;
    }
}
