<?php 

class Object_BlogArticle extends Object_Concrete {

public $o_classId = 5;
public $o_className = "blogArticle";
public $localizedfields;
public $date;
public $categories;
public $posterImage;


/**
* @param array $values
* @return Object_BlogArticle
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
public function getText ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("text", $language);
	$preValue = $this->preGetValue("text"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	 return $data;
}

/**
* @return string
*/
public function getTags ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("tags", $language);
	$preValue = $this->preGetValue("tags"); 
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
* @param string $text
* @return void
*/
public function setText ($text, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("text", $text, $language);
	return $this;
}

/**
* @param string $tags
* @return void
*/
public function setTags ($tags, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("tags", $tags, $language);
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
* @return array
*/
public function getCategories () {
	$preValue = $this->preGetValue("categories"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("categories")->preGetData($this);
	 return $data;
}

/**
* @param array $categories
* @return void
*/
public function setCategories ($categories) {
	$this->categories = $this->getClass()->getFieldDefinition("categories")->preSetData($this, $categories);
	return $this;
}

/**
* @return Object_Data_Hotspotimage
*/
public function getPosterImage () {
	$preValue = $this->preGetValue("posterImage"); 
	if($preValue !== null && !Pimcore::inAdmin()) { return $preValue;}
	$data = $this->posterImage;
	 return $data;
}

/**
* @param Object_Data_Hotspotimage $posterImage
* @return void
*/
public function setPosterImage ($posterImage) {
	$this->posterImage = $posterImage;
	return $this;
}

protected static $_relationFields = array (
  'categories' => 
  array (
    'type' => 'objects',
  ),
);

public $lazyLoadedFields = NULL;

}

