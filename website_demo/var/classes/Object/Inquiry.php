<?php 

class Object_Inquiry extends Object_Concrete {

public $o_classId = 3;
public $o_className = "inquiry";
public $date;
public $person;
public $message;
public $terms;


/**
* @param array $values
* @return Object_Inquiry
*/
public static function create($values = array()) {
	$object = new self();
	$object->setValues($values);
	return $object;
}

/**
* @return Zend_Date
*/
public function getDate () {
	$preValue = $this->preGetValue("date"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->date;
	 return $data;
}

/**
* @param Zend_Date $date
* @return void
*/
public function setDate ($date) {
	$this->date = $date;
	return $this;
}

/**
* @return Document_Page | Document_Snippet | Document | Asset | Object_Abstract
*/
public function getPerson () {
	$preValue = $this->preGetValue("person"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("person")->preGetData($this);
	 return $data;
}

/**
* @param Document_Page | Document_Snippet | Document | Asset | Object_Abstract $person
* @return void
*/
public function setPerson ($person) {
	$this->person = $this->getClass()->getFieldDefinition("person")->preSetData($this, $person);
	return $this;
}

/**
* @return string
*/
public function getMessage () {
	$preValue = $this->preGetValue("message"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->message;
	 return $data;
}

/**
* @param string $message
* @return void
*/
public function setMessage ($message) {
	$this->message = $message;
	return $this;
}

/**
* @return boolean
*/
public function getTerms () {
	$preValue = $this->preGetValue("terms"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->terms;
	 return $data;
}

/**
* @param boolean $terms
* @return void
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

