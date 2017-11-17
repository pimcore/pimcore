<?php 

/** 
* Generated at: 2017-11-13T11:54:03+01:00
* Inheritance: yes
* Variants: yes
* Changed by: admin (3)
* IP: 192.168.9.17


Fields Summary: 
- localizedfields [localizedfields]
-- number1 [numeric]
-- number2 [numeric]
-- number3 [numeric]
-- myhref [href]
-- title [input]
-- shortText [textarea]
-- text [wysiwyg]
-- cbx [checkbox]
- mulihref [multihref]
- mulihref2 [multihref]
- date [datetime]
- image_1 [image]
- image_2 [image]
- image_3 [image]
- xcxysdf [href]
- fc [fieldcollections]
- fuffifeld [fieldcollections]
- thebrick [objectbricks]
- iwh [hotspotimage]
- theobjects [objects]
*/ 

namespace Pimcore\Model\DataObject;



/**
* @method \Pimcore\Model\DataObject\News\Listing getByLocalizedfields ($field, $value, $locale = null, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByMulihref ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByMulihref2 ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByDate ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByImage_1 ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByImage_2 ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByImage_3 ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByXcxysdf ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByFc ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByFuffifeld ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByThebrick ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByIwh ($value, $limit = 0) 
* @method static \Pimcore\Model\DataObject\News\Listing getByTheobjects ($value, $limit = 0) 
*/

class News extends Concrete {

public $o_classId = 2;
public $o_className = "news";
public $localizedfields;
public $mulihref;
public $mulihref2;
public $date;
public $image_1;
public $image_2;
public $image_3;
public $xcxysdf;
public $fc;
public $fuffifeld;
public $thebrick;
public $iwh;
public $theobjects;


/**
* @param array $values
* @return \Pimcore\Model\DataObject\News
*/
public static function create($values = array()) {
	$object = new static();
	$object->setValues($values);
	return $object;
}

/**
* Get localizedfields - 
* @return \Pimcore\Model\DataObject\Localizedfield
*/
public function getLocalizedfields () {
	$preValue = $this->preGetValue("localizedfields"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("localizedfields")->preGetData($this);
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("localizedfields")->isEmpty($data)) {
		return $this->getValueFromParent("localizedfields");
	}
	return $data;
}

/**
* Get number1 - number1
* @return float
*/
public function getNumber1 ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("number1", $language);
	$preValue = $this->preGetValue("number1"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get number2 - number2
* @return float
*/
public function getNumber2 ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("number2", $language);
	$preValue = $this->preGetValue("number2"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get number3 - number3
* @return float
*/
public function getNumber3 ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("number3", $language);
	$preValue = $this->preGetValue("number3"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get myhref - myhref
* @return \Pimcore\Model\Document\Page | \Pimcore\Model\Document\Snippet | \Pimcore\Model\Document | \Pimcore\Model\Asset | \Pimcore\Model\DataObject\AbstractObject
*/
public function getMyhref ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("myhref", $language);
	$preValue = $this->preGetValue("myhref"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get title - Title
* @return string
*/
public function getTitle ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("title", $language);
	$preValue = $this->preGetValue("title"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get shortText - Short Text
* @return string
*/
public function getShortText ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("shortText", $language);
	$preValue = $this->preGetValue("shortText"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get text - Text
* @return string
*/
public function getText ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("text", $language);
	$preValue = $this->preGetValue("text"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Get cbx - cbx
* @return boolean
*/
public function getCbx ($language = null) {
	$data = $this->getLocalizedfields()->getLocalizedValue("cbx", $language);
	$preValue = $this->preGetValue("cbx"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	 return $data;
}

/**
* Set localizedfields - 
* @param \Pimcore\Model\DataObject\Localizedfield $localizedfields
* @return \Pimcore\Model\DataObject\News
*/
public function setLocalizedfields ($localizedfields) {
	$this->localizedfields = $localizedfields;
	return $this;
}

/**
* Set number1 - number1
* @param float $number1
* @return \Pimcore\Model\DataObject\News
*/
public function setNumber1 ($number1, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("number1", $number1, $language);
	return $this;
}

/**
* Set number2 - number2
* @param float $number2
* @return \Pimcore\Model\DataObject\News
*/
public function setNumber2 ($number2, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("number2", $number2, $language);
	return $this;
}

/**
* Set number3 - number3
* @param float $number3
* @return \Pimcore\Model\DataObject\News
*/
public function setNumber3 ($number3, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("number3", $number3, $language);
	return $this;
}

/**
* Set myhref - myhref
* @param \Pimcore\Model\Document\Page | \Pimcore\Model\Document\Snippet | \Pimcore\Model\Document | \Pimcore\Model\Asset | \Pimcore\Model\DataObject\AbstractObject $myhref
* @return \Pimcore\Model\DataObject\News
*/
public function setMyhref ($myhref, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("myhref", $myhref, $language);
	return $this;
}

/**
* Set title - Title
* @param string $title
* @return \Pimcore\Model\DataObject\News
*/
public function setTitle ($title, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("title", $title, $language);
	return $this;
}

/**
* Set shortText - Short Text
* @param string $shortText
* @return \Pimcore\Model\DataObject\News
*/
public function setShortText ($shortText, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("shortText", $shortText, $language);
	return $this;
}

/**
* Set text - Text
* @param string $text
* @return \Pimcore\Model\DataObject\News
*/
public function setText ($text, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("text", $text, $language);
	return $this;
}

/**
* Set cbx - cbx
* @param boolean $cbx
* @return \Pimcore\Model\DataObject\News
*/
public function setCbx ($cbx, $language = null) {
	$this->getLocalizedfields()->setLocalizedValue("cbx", $cbx, $language);
	return $this;
}

/**
* Get mulihref - mulihref
* @return \Pimcore\Model\DataObject\AbstractObject[]
*/
public function getMulihref () {
	$preValue = $this->preGetValue("mulihref"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("mulihref")->preGetData($this);
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("mulihref")->isEmpty($data)) {
		return $this->getValueFromParent("mulihref");
	}
	return $data;
}

/**
* Set mulihref - mulihref
* @param \Pimcore\Model\DataObject\AbstractObject[] $mulihref
* @return \Pimcore\Model\DataObject\News
*/
public function setMulihref ($mulihref) {
	$this->mulihref = $this->getClass()->getFieldDefinition("mulihref")->preSetData($this, $mulihref);
	return $this;
}

/**
* Get mulihref2 - multihref2
* @return \Pimcore\Model\DataObject\AbstractObject[]
*/
public function getMulihref2 () {
	$preValue = $this->preGetValue("mulihref2"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("mulihref2")->preGetData($this);
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("mulihref2")->isEmpty($data)) {
		return $this->getValueFromParent("mulihref2");
	}
	return $data;
}

/**
* Set mulihref2 - multihref2
* @param \Pimcore\Model\DataObject\AbstractObject[] $mulihref2
* @return \Pimcore\Model\DataObject\News
*/
public function setMulihref2 ($mulihref2) {
	$this->mulihref2 = $this->getClass()->getFieldDefinition("mulihref2")->preSetData($this, $mulihref2);
	return $this;
}

/**
* Get date - Date
* @return \Carbon\Carbon
*/
public function getDate () {
	$preValue = $this->preGetValue("date"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->date;
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("date")->isEmpty($data)) {
		return $this->getValueFromParent("date");
	}
	return $data;
}

/**
* Set date - Date
* @param \Carbon\Carbon $date
* @return \Pimcore\Model\DataObject\News
*/
public function setDate ($date) {
	$this->date = $date;
	return $this;
}

/**
* Get image_1 - Image
* @return \Pimcore\Model\Asset\Image
*/
public function getImage_1 () {
	$preValue = $this->preGetValue("image_1"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->image_1;
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("image_1")->isEmpty($data)) {
		return $this->getValueFromParent("image_1");
	}
	return $data;
}

/**
* Set image_1 - Image
* @param \Pimcore\Model\Asset\Image $image_1
* @return \Pimcore\Model\DataObject\News
*/
public function setImage_1 ($image_1) {
	$this->image_1 = $image_1;
	return $this;
}

/**
* Get image_2 - Image
* @return \Pimcore\Model\Asset\Image
*/
public function getImage_2 () {
	$preValue = $this->preGetValue("image_2"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->image_2;
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("image_2")->isEmpty($data)) {
		return $this->getValueFromParent("image_2");
	}
	return $data;
}

/**
* Set image_2 - Image
* @param \Pimcore\Model\Asset\Image $image_2
* @return \Pimcore\Model\DataObject\News
*/
public function setImage_2 ($image_2) {
	$this->image_2 = $image_2;
	return $this;
}

/**
* Get image_3 - Image
* @return \Pimcore\Model\Asset\Image
*/
public function getImage_3 () {
	$preValue = $this->preGetValue("image_3"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->image_3;
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("image_3")->isEmpty($data)) {
		return $this->getValueFromParent("image_3");
	}
	return $data;
}

/**
* Set image_3 - Image
* @param \Pimcore\Model\Asset\Image $image_3
* @return \Pimcore\Model\DataObject\News
*/
public function setImage_3 ($image_3) {
	$this->image_3 = $image_3;
	return $this;
}

/**
* Get xcxysdf - xcxysdf
* @return 
*/
public function getXcxysdf () {
	$preValue = $this->preGetValue("xcxysdf"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("xcxysdf")->preGetData($this);
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("xcxysdf")->isEmpty($data)) {
		return $this->getValueFromParent("xcxysdf");
	}
	return $data;
}

/**
* Set xcxysdf - xcxysdf
* @param  $xcxysdf
* @return \Pimcore\Model\DataObject\News
*/
public function setXcxysdf ($xcxysdf) {
	$this->xcxysdf = $this->getClass()->getFieldDefinition("xcxysdf")->preSetData($this, $xcxysdf);
	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection
*/
public function getFc () {
	$preValue = $this->preGetValue("fc"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("fc")->preGetData($this);
	 return $data;
}

/**
* Set fc - fc
* @param \Pimcore\Model\DataObject\Fieldcollection $fc
* @return \Pimcore\Model\DataObject\News
*/
public function setFc ($fc) {
	$this->fc = $this->getClass()->getFieldDefinition("fc")->preSetData($this, $fc);
	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Fieldcollection
*/
public function getFuffifeld () {
	$preValue = $this->preGetValue("fuffifeld"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}
	$data = $this->getClass()->getFieldDefinition("fuffifeld")->preGetData($this);
	 return $data;
}

/**
* Set fuffifeld - fuffifeld
* @param \Pimcore\Model\DataObject\Fieldcollection $fuffifeld
* @return \Pimcore\Model\DataObject\News
*/
public function setFuffifeld ($fuffifeld) {
	$this->fuffifeld = $this->getClass()->getFieldDefinition("fuffifeld")->preSetData($this, $fuffifeld);
	return $this;
}

/**
* @return \Pimcore\Model\DataObject\Objectbrick
*/
public function getThebrick () {
	$data = $this->thebrick;
	if(!$data) { 
		if(\Pimcore\Tool::classExists("\\Pimcore\\Model\\DataObject\\News\\Thebrick")) { 
			$data = new \Pimcore\Model\DataObject\News\Thebrick($this, "thebrick");
			$this->thebrick = $data;
		} else {
			return null;
		}
	}
	$preValue = $this->preGetValue("thebrick"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { return $preValue;}
	 return $data;
}

/**
* Set thebrick - thebrick
* @param \Pimcore\Model\DataObject\Objectbrick $thebrick
* @return \Pimcore\Model\DataObject\News
*/
public function setThebrick ($thebrick) {
	$this->thebrick = $this->getClass()->getFieldDefinition("thebrick")->preSetData($this, $thebrick);
	return $this;
}

/**
* Get iwh - iwh
* @return \Pimcore\Model\DataObject\Data\Hotspotimage
*/
public function getIwh () {
	$preValue = $this->preGetValue("iwh"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->iwh;
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("iwh")->isEmpty($data)) {
		return $this->getValueFromParent("iwh");
	}
	return $data;
}

/**
* Set iwh - iwh
* @param \Pimcore\Model\DataObject\Data\Hotspotimage $iwh
* @return \Pimcore\Model\DataObject\News
*/
public function setIwh ($iwh) {
	$this->iwh = $iwh;
	return $this;
}

/**
* Get theobjects - theobjects
* @return \Pimcore\Model\DataObject\blogArticle[]
*/
public function getTheobjects () {
	$preValue = $this->preGetValue("theobjects"); 
	if($preValue !== null && !\Pimcore::inAdmin()) { 
		return $preValue;
	}
	$data = $this->getClass()->getFieldDefinition("theobjects")->preGetData($this);
	if(\Pimcore\Model\DataObject::doGetInheritedValues() && $this->getClass()->getFieldDefinition("theobjects")->isEmpty($data)) {
		return $this->getValueFromParent("theobjects");
	}
	return $data;
}

/**
* Set theobjects - theobjects
* @param \Pimcore\Model\DataObject\blogArticle[] $theobjects
* @return \Pimcore\Model\DataObject\News
*/
public function setTheobjects ($theobjects) {
	$this->theobjects = $this->getClass()->getFieldDefinition("theobjects")->preSetData($this, $theobjects);
	return $this;
}

protected static $_relationFields = array (
  'mulihref' => 
  array (
    'type' => 'multihref',
  ),
  'mulihref2' => 
  array (
    'type' => 'multihref',
  ),
  'xcxysdf' => 
  array (
    'type' => 'href',
  ),
  'theobjects' => 
  array (
    'type' => 'objects',
  ),
);

public $lazyLoadedFields = array (
  0 => 'xcxysdf',
  1 => 'fc',
  2 => 'fuffifeld',
  3 => 'theobjects',
);

}

