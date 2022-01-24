<?php

/**
Fields Summary:
- field [indexFieldSelectionCombo]
- direction [select]
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class OrderByFields extends DataObject\Fieldcollection\Data\AbstractData
{
    protected $type = "OrderByFields";
    protected $field;
    protected $direction;


    /**
     * Get field - Field
     * @return string|null
     */
    public function getField(): ?string
    {
        $data = $this->field;
        if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
            return $data->getPlain();
        }

        return $data;
    }

    /**
     * Set field - Field
     * @param string|null $field
     * @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderByFields
     */
    public function setField(?string $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Get direction - Direction
     * @return string|null
     */
    public function getDirection(): ?string
    {
        $data = $this->direction;
        if ($data instanceof \Pimcore\Model\DataObject\Data\EncryptedField) {
            return $data->getPlain();
        }

        return $data;
    }

    /**
     * Set direction - Direction
     * @param string|null $direction
     * @return \Pimcore\Model\DataObject\Fieldcollection\Data\OrderByFields
     */
    public function setDirection(?string $direction)
    {
        $this->direction = $direction;

        return $this;
    }

}

