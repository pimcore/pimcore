<?php 

/** 
* Generated at: 2016-08-09T09:00:04+02:00
* Inheritance: no
* Variants: no
* IP: 192.168.11.111


Fields Summary: 
- localizedfields [localizedfields]
-- name [input]
*/ 

namespace Pimcore\Model\Object;



/**
* @method static \Pimcore\Model\Object\BlogCategory\Listing getByLocalizedfields ($field, $value, $locale = null, $limit = 0) 
*/

class BlogCategory extends Concrete {

public $o_classId = 6;
public $o_className = "blogCategory";
public $localizedfields;


/**
* @param array $values
* @return \Pimcore\Model\Object\BlogCategory
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get localizedfields - 
* @return \Pimcore\Model\Object\Localizedfield
*/
public function getLocalizedfields () {
	$preValue = $this->preGetValue("localizedfields"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("localizedfields")->preGetData($this);
	return $data;
}

/**
* Get name - Name
* @return string
*/
public function getName ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("name", $language);
	$preValue = $this->preGetValue("name"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Set localizedfields - 
* @param \Pimcore\Model\Object\Localizedfield $localizedfields
* @return \Pimcore\Model\Object\BlogCategory
*/
public function setLocalizedfields ($localizedfields) {
	$this->localizedfields = $localizedfields;
	return $this;
}

/**
* Set name - Name
* @param string $name
* @return \Pimcore\Model\Object\BlogCategory
*/
public function setName ($name, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("name", $name, $language);
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

