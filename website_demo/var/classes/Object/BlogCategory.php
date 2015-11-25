<?php 

/** Generated at 2015-11-17T06:57:25+01:00 */

/**
* Inheritance: no
* Variants   : no
* Changed by : admin (37)
* IP:          192.168.11.33
*/


namespace Pimcore\Model\Object;



/**
* @method static \Pimcore\Model\Object\BlogCategory getByLocalizedfields ($value, $limit = 0) 
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
* @return array
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
* @param array $localizedfields
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

