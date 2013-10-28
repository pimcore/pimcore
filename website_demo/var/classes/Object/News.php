<?php 

class Object_News extends Object_Concrete {

public $o_classId = 2;
public $o_className = "news";
public $localizedfields;
public $date;
public $image_1;
public $image_2;
public $image_3;


/**
* @param array $values
* @return Object_News
*/
public static function create($values = array()) {
	$object = new self();
	$object->setValues($values);
	return $object;
}

/**
* @return array
*/
public function getLocalizedfields () {
	$preValue = $this->preGetValue("localizedfields"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("localizedfields")->preGetData($this);
	 return $data;
}

/**
* @return string
*/
public function getTitle ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("title", $language);
	$preValue = $this->preGetValue("title"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	 return $data;
}

/**
* @return string
*/
public function getShortText ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("shortText", $language);
	$preValue = $this->preGetValue("shortText"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	 return $data;
}

/**
* @return string
*/
public function getText ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("text", $language);
	$preValue = $this->preGetValue("text"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	 return $data;
}

/**
* @param array $localizedfields
* @return void
*/
public function setLocalizedfields ($localizedfields) {
	$this->localizedfields = $localizedfields;
	return $this;
}

/**
* @param string $title
* @return void
*/
public function setTitle ($title, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("title", $title, $language);
	return $this;
}

/**
* @param string $shortText
* @return void
*/
public function setShortText ($shortText, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("shortText", $shortText, $language);
	return $this;
}

/**
* @param string $text
* @return void
*/
public function setText ($text, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("text", $text, $language);
	return $this;
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
* @return Asset_Image
*/
public function getImage_1 () {
	$preValue = $this->preGetValue("image_1"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->image_1;
	 return $data;
}

/**
* @param Asset_Image $image_1
* @return void
*/
public function setImage_1 ($image_1) {
	$this->image_1 = $image_1;
	return $this;
}

/**
* @return Asset_Image
*/
public function getImage_2 () {
	$preValue = $this->preGetValue("image_2"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->image_2;
	 return $data;
}

/**
* @param Asset_Image $image_2
* @return void
*/
public function setImage_2 ($image_2) {
	$this->image_2 = $image_2;
	return $this;
}

/**
* @return Asset_Image
*/
public function getImage_3 () {
	$preValue = $this->preGetValue("image_3"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->image_3;
	 return $data;
}

/**
* @param Asset_Image $image_3
* @return void
*/
public function setImage_3 ($image_3) {
	$this->image_3 = $image_3;
	return $this;
}

protected static $_relationFields = array (
);

public $lazyLoadedFields = NULL;

}

