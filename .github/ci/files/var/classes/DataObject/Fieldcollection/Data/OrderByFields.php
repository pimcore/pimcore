<?php
declare(strict_types=1);

/**
 * Fields Summary:
 * - field [indexFieldSelectionCombo]
 * - direction [select]
 */

namespace Pimcore\Model\DataObject\Fieldcollection\Data;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

class OrderByFields extends DataObject\Fieldcollection\Data\AbstractData
{
protected string $type = "OrderByFields";
protected ?string $field;
protected ?string $direction;


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
* @return $this
*/
public function setField(?string $field): static
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
* @return $this
*/
public function setDirection(?string $direction): static
{
	$this->direction = $direction;

	return $this;
}

}

