<?php 

class Object_News extends Object_Concrete {

public $o_classId = 2;
public $o_className = "news";
public $date;
public $title;
public $shortText;
public $text;
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
* @return string
*/
public function getTitle () {
	$preValue = $this->preGetValue("title"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->title;
	 return $data;
}

/**
* @param string $title
* @return void
*/
public function setTitle ($title) {
	$this->title = $title;
	return $this;
}

/**
* @return string
*/
public function getShortText () {
	$preValue = $this->preGetValue("shortText"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->shortText;
	 return $data;
}

/**
* @param string $shortText
* @return void
*/
public function setShortText ($shortText) {
	$this->shortText = $shortText;
	return $this;
}

/**
* @return string
*/
public function getText () {
	$preValue = $this->preGetValue("text"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("text")->preGetData($this);
	 return $data;
}

/**
* @param string $text
* @return void
*/
public function setText ($text) {
	$this->text = $text;
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

