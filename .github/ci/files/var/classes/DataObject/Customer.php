<?php
declare(strict_types=1);

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

    public static function create(array $values = array()): Customer
{
	$object = new static();
	$object->setValues($values);
	return $object;
}

}
