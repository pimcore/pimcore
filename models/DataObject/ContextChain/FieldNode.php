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
 * @category   Pimcore
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ContextChain;

use Pimcore\Model\DataObject\ClassDefinition\Data;

class FieldNode extends AbstractNode
{

    /** @var Data */
    protected $fieldDefinition;

    /**
     * @internal
     *
     * FieldNode constructor.
     * @param Data $fieldDefinition
     */
    public function __construct(Data $fieldDefinition)
    {
        $this->fieldDefinition = $fieldDefinition;
    }

    /**
     * @return string
     */
    public function getFieldname() {
        return $this->getFieldDefinition()->getName();
    }

    /**
     * @return Data
     */
    public function getFieldDefinition(): Data
    {
        return $this->fieldDefinition;
    }
}
