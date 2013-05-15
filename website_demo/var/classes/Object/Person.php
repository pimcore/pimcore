<?php 

class Object_Person extends Object_Concrete {

public $o_classId = 4;
public $o_className = "person";
public $gender;
public $firstname;
public $lastname;
public $email;
public $newsletterActive;
public $newsletterConfirmed;
public $dateRegister;


/**
* @param array $values
* @return Object_Person
*/
public static function create($values = array()) {
	$object = new self();
	$object->setValues($values);
	return $object;
}

/**
* @return string
*/
public function getGender () {
	$preValue = $this->preGetValue("gender"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->gender;
	 return $data;
}

/**
* @param string $gender
* @return void
*/
public function setGender ($gender) {
	$this->gender = $gender;
	return $this;
}

/**
* @return string
*/
public function getFirstname () {
	$preValue = $this->preGetValue("firstname"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->firstname;
	 return $data;
}

/**
* @param string $firstname
* @return void
*/
public function setFirstname ($firstname) {
	$this->firstname = $firstname;
	return $this;
}

/**
* @return string
*/
public function getLastname () {
	$preValue = $this->preGetValue("lastname"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->lastname;
	 return $data;
}

/**
* @param string $lastname
* @return void
*/
public function setLastname ($lastname) {
	$this->lastname = $lastname;
	return $this;
}

/**
* @return string
*/
public function getEmail () {
	$preValue = $this->preGetValue("email"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->email;
	 return $data;
}

/**
* @param string $email
* @return void
*/
public function setEmail ($email) {
	$this->email = $email;
	return $this;
}

/**
* @return boolean
*/
public function getNewsletterActive () {
	$preValue = $this->preGetValue("newsletterActive"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->newsletterActive;
	 return $data;
}

/**
* @param boolean $newsletterActive
* @return void
*/
public function setNewsletterActive ($newsletterActive) {
	$this->newsletterActive = $newsletterActive;
	return $this;
}

/**
* @return boolean
*/
public function getNewsletterConfirmed () {
	$preValue = $this->preGetValue("newsletterConfirmed"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->newsletterConfirmed;
	 return $data;
}

/**
* @param boolean $newsletterConfirmed
* @return void
*/
public function setNewsletterConfirmed ($newsletterConfirmed) {
	$this->newsletterConfirmed = $newsletterConfirmed;
	return $this;
}

/**
* @return Zend_Date
*/
public function getDateRegister () {
	$preValue = $this->preGetValue("dateRegister"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->dateRegister;
	 return $data;
}

/**
* @param Zend_Date $dateRegister
* @return void
*/
public function setDateRegister ($dateRegister) {
	$this->dateRegister = $dateRegister;
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

