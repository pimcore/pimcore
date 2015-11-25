<?php 

/** Generated at 2015-11-17T06:56:43+01:00 */

/**
* Inheritance: no
* Variants   : no
* Changed by : admin (37)
* IP:          192.168.11.33
*/


namespace Pimcore\Model\Object;



/**
* @method static \Pimcore\Model\Object\Person getByGender ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByFirstname ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByLastname ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByEmail ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByNewsletterActive ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByNewsletterConfirmed ($value, $limit = 0) 
* @method static \Pimcore\Model\Object\Person getByDateRegister ($value, $limit = 0) 
*/

class Person extends Concrete {

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
* @return \Pimcore\Model\Object\Person
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get gender - Gender
* @return string
*/
public function getGender () {
	$preValue = $this->preGetValue("gender"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->gender;
	return $data;
}

/**
* Set gender - Gender
* @param string $gender
* @return \Pimcore\Model\Object\Person
*/
public function setGender ($gender) {
	$this->gender = $gender;
	return $this;
}

/**
* Get firstname - Firstname
* @return string
*/
public function getFirstname () {
	$preValue = $this->preGetValue("firstname"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->firstname;
	return $data;
}

/**
* Set firstname - Firstname
* @param string $firstname
* @return \Pimcore\Model\Object\Person
*/
public function setFirstname ($firstname) {
	$this->firstname = $firstname;
	return $this;
}

/**
* Get lastname - Lastname
* @return string
*/
public function getLastname () {
	$preValue = $this->preGetValue("lastname"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->lastname;
	return $data;
}

/**
* Set lastname - Lastname
* @param string $lastname
* @return \Pimcore\Model\Object\Person
*/
public function setLastname ($lastname) {
	$this->lastname = $lastname;
	return $this;
}

/**
* Get email - Email
* @return string
*/
public function getEmail () {
	$preValue = $this->preGetValue("email"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->email;
	return $data;
}

/**
* Set email - Email
* @param string $email
* @return \Pimcore\Model\Object\Person
*/
public function setEmail ($email) {
	$this->email = $email;
	return $this;
}

/**
* Get newsletterActive - Newsletter Active
* @return boolean
*/
public function getNewsletterActive () {
	$preValue = $this->preGetValue("newsletterActive"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->newsletterActive;
	return $data;
}

/**
* Set newsletterActive - Newsletter Active
* @param boolean $newsletterActive
* @return \Pimcore\Model\Object\Person
*/
public function setNewsletterActive ($newsletterActive) {
	$this->newsletterActive = $newsletterActive;
	return $this;
}

/**
* Get newsletterConfirmed - Newsletter Confirmed
* @return boolean
*/
public function getNewsletterConfirmed () {
	$preValue = $this->preGetValue("newsletterConfirmed"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->newsletterConfirmed;
	return $data;
}

/**
* Set newsletterConfirmed - Newsletter Confirmed
* @param boolean $newsletterConfirmed
* @return \Pimcore\Model\Object\Person
*/
public function setNewsletterConfirmed ($newsletterConfirmed) {
	$this->newsletterConfirmed = $newsletterConfirmed;
	return $this;
}

/**
* Get dateRegister - dateRegister
* @return \Pimcore\Date
*/
public function getDateRegister () {
	$preValue = $this->preGetValue("dateRegister"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->dateRegister;
	return $data;
}

/**
* Set dateRegister - dateRegister
* @param \Pimcore\Date $dateRegister
* @return \Pimcore\Model\Object\Person
*/
public function setDateRegister ($dateRegister) {
	$this->dateRegister = $dateRegister;
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

