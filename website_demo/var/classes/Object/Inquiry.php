<?php 

/** Generated at 2015-11-17T06:56:34+01:00 */

/**
* Inheritance: no
* Variants   : no
* Changed by : admin (37)
* IP:          192.168.11.33
*/


namespace Pimcore\Model\Object;



/**
* @method static \Pimcore\Model\Object\Inquiry getByDate ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Inquiry getByPerson ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Inquiry getByMessage ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Inquiry getByTerms ($value, $limit = 0) 
*/

class Inquiry extends Concrete {

public $o_classId = 3;
public $o_className = "inquiry";
public $date;
public $person;
public $message;
public $terms;


/**
* @param array $values
* @return \Pimcore\Model\Object\Inquiry
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get date - Date
* @return \Pimcore\Date
*/
public function getDate () {
	$preValue = $this->preGetValue("date"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->date;
	return $data;
}

/**
* Set date - Date
* @param \Pimcore\Date $date
* @return \Pimcore\Model\Object\Inquiry
*/
public function setDate ($date) {
	$this->date = $date;
	return $this;
}

/**
* Get person - Person
* @return \Pimcore\Model\Document\Page | \Pimcore\Model\Document\Snippet | \Pimcore\Model\Document | \Pimcore\Model\Asset | \Pimcore\Model\Object\AbstractObject
*/
public function getPerson () {
	$preValue = $this->preGetValue("person"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("person")->preGetData($this);
	return $data;
}

/**
* Set person - Person
* @param \Pimcore\Model\Document\Page | \Pimcore\Model\Document\Snippet | \Pimcore\Model\Document | \Pimcore\Model\Asset | \Pimcore\Model\Object\AbstractObject $person
* @return \Pimcore\Model\Object\Inquiry
*/
public function setPerson ($person) {
	$this->person = $this->getClass()->getFieldDefinition("person")->preSetData($this, $person);
	return $this;
}

/**
* Get message - Message
* @return string
*/
public function getMessage () {
	$preValue = $this->preGetValue("message"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->message;
	return $data;
}

/**
* Set message - Message
* @param string $message
* @return \Pimcore\Model\Object\Inquiry
*/
public function setMessage ($message) {
	$this->message = $message;
	return $this;
}

/**
* Get terms - Terms of Use
* @return boolean
*/
public function getTerms () {
	$preValue = $this->preGetValue("terms"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->terms;
	return $data;
}

/**
* Set terms - Terms of Use
* @param boolean $terms
* @return \Pimcore\Model\Object\Inquiry
*/
public function setTerms ($terms) {
	$this->terms = $terms;
	return $this;
}

protected static $_relationFields = array (
  'person' => 
  array (
    'type' => 'href',
  ),
);

public $lazyLoadedFields = NULL;

}

