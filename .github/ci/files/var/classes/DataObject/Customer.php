<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
*/

namespace Pimcore\Model\DataObject;

use Pimcore\Model\DataObject\Exception\InheritanceParentNotFoundException;
use Pimcore\Model\DataObject\PreGetValueHookInterface;

/**
* @method static \Pimcore\Model\DataObject\Customer\Listing getList()
*/

class Customer extends Concrete
{
protected $o_classId = "CU";
protected $o_className = "Customer";

/**
* @param array $values
* @return \Pimcore\Model\DataObject\Customer
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

}
