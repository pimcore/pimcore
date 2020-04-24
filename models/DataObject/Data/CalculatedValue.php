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
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ContextChain\BlockElementNode;
use Pimcore\Model\DataObject\ContextChain\BlockNode;
use Pimcore\Model\DataObject\ContextChain\ClassificationstoreFieldNode;
use Pimcore\Model\DataObject\ContextChain\ClassificationstoreNode;
use Pimcore\Model\DataObject\ContextChain\FieldcollectionItemNode;
use Pimcore\Model\DataObject\ContextChain\FieldcollectionNode;
use Pimcore\Model\DataObject\ContextChain\FieldNode;
use Pimcore\Model\DataObject\ContextChain\LocalizedfieldNode;
use Pimcore\Model\DataObject\ContextChain\ObjectbrickNode;
use Pimcore\Model\DataObject\ContextChain\ObjectNode;
use Pimcore\Model\DataObject\ContextChain\OwnerChain;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;

class CalculatedValue implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;

    /**
     * @deprecated
     *
     * @var string
     */
    protected $fieldname;

    /**
     * @deprecated
     *
     * @var string
     */
    protected $ownerType = 'object';

    /**
     * @deprecated
     *
     * @var string
     */
    protected $ownerName;

    /**
     * @deprecated
     *
     * @var int
     */
    protected $index;

    /**
     * @deprecated
     *
     * @var string
     */
    protected $position;

    /**
     * @deprecated
     *
     * @var int
     */
    protected $groupId;

    /**
     * @deprecated
     *
     * @var int
     */
    protected $keyId;

    /** @var OwnerChain */
    protected $ownerChain;

    /**
     * @var mixed
     */
    protected $keyDefinition;

    /**
     * @internal
     *
     * CalculatedValue constructor.
     *
     * @param string $fieldname
     */
    public function __construct($fieldname = null)
    {
        $this->fieldname = $fieldname;
        $this->markMeDirty();
    }

    /**
     * @param OwnerChain $ownerChain
     *
     * @return $this
     */
    public function setOwnerChain(OwnerChain $ownerChain) {
        $this->ownerChain = $ownerChain;
        $this->markMeDirty();
        return $this;
    }

    /**
     * @return OwnerChain
     */
    public function getOwnerChain()
    {
        return $this->ownerChain;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return string|null
     */
    public function getFieldname()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            /** @var FieldNode $current */
            $current = $chain->current();
            return $current->getFieldname();
        }
        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return int|null
     */
    public function getIndex()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ($chain->valid()) {
                $current = $chain->current();
                if ($current instanceof BlockElementNode || $current instanceof FieldcollectionItemNode) {
                    return $current->getIndex();
                }
                $chain->next();;
            }
        }

        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return string|null
     */
    public function getOwnerName()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ($chain->valid()) {
                $current = $chain->current();
                if ($current instanceof LocalizedfieldNode || $current instanceof ObjectbrickNode
                    || $current instanceof FieldcollectionNode || $current instanceof ClassificationstoreNode
                    || $current instanceof BlockNode) {
                    return $current->getFieldname();
                }

                $chain->next();
            }
        }

        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return string|null
     */
    public function getOwnerType()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ($chain->valid()) {
                $current = $chain->current();
                if ($current instanceof LocalizedfieldNode) {
                    return "localizedfield";
                } else if ($current instanceof ObjectbrickNode) {
                    return "objectbrick";
                } else if ($current instanceof FieldcollectionItemNode) {
                    return "fieldcollection";
                } else if ($current instanceof ClassificationstoreNode) {
                    return "classificationstore";
                } else if ($current instanceof BlockNode) {
                    return "block";
                } else if ($current instanceof ObjectNode) {
                    return "object";
                }
                $chain->next();
            }
        }

        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return string|null
     */
    public function getPosition()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ($chain->valid()) {
                $current = $chain->current();
                if ($current instanceof LocalizedfieldNode) {
                    return $current->getLanguage();
                } else if ($current instanceof ClassificationstoreFieldNode) {
                    return $current->getLanguage();
                }
                $chain->next();
            }
        }

        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return int|null
     */
    public function getGroupId()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ($chain->valid()) {
                $current = $chain->current();
                if ($current instanceof ClassificationstoreFieldNode) {
                    return $current->getGroupId();
                }
                $chain->next();
            }
        }
        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return Data|null
     */
    public function getKeyDefinition()
    {
        $chain = $this->getOwnerChain();

        if ($chain) {
            $chain->rewind();
            $current = $chain->current();
            if ($current instanceof FieldNode) {
                return $current->getFieldDefinition();
            }
        }
        return null;
    }

    /**
     * @deprecated use owner chain instead
     *
     * @return int|null
     */
    public function getKeyId()
    {
        $chain = $this->getOwnerChain();
        if ($chain) {
            $chain->rewind();
            while ( $chain->valid()) {
                $current = $chain->current();
                if ($current instanceof ClassificationstoreFieldNode) {
                    return $current->getKeyId();
                }
                $chain->next();
            }
        }
        return null;
    }
}
