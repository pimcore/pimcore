<?php

/**
* Inheritance: no
* Variants: no


Fields Summary:
- active [checkbox]
- gender [gender]
- firstname [firstname]
- lastname [lastname]
- company [input]
- email [email]
- street [input]
- zip [input]
- city [input]
- countryCode [country]
- phone [input]
- idEncoded [input]
- customerLanguage [language]
- newsletter [consent]
- newsletterConfirmed [newsletterConfirmed]
- newsletterConfirmToken [input]
- profiling [consent]
- manualSegments [advancedManyToManyObjectRelation]
- calculatedSegments [advancedManyToManyObjectRelation]
- password [password]
- ssoIdentities [manyToManyObjectRelation]
- passwordRecoveryToken [input]
- passwordRecoveryTokenDate [datetime]
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
