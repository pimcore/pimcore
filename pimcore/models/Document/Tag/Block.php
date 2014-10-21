<?php
/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Model;

class Block extends Model\Document\Tag {

    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = array();

    /**
     * Current step of the block while iteration
     *
     * @var integer
     */
    public $current = 0;

    /**
     * @var string[]
     */
    public $suffixes = array();

    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "block";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return $this->indices;
    }

    /**
     * @see Document\Tag\TagInterface::admin
     */
    public function admin() {
        // nothing to do
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     */
    public function frontend() {
        // nothing to do
        return null;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->indices = \Pimcore\Tool\Serialize::unserialize($data);
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->indices = $data;
        return $this;
    }

    /**
     * @return void
     */
    public function setDefault() {
        if (empty($this->indices) && isset($this->options["default"]) && $this->options["default"]) {
            for ($i = 0; $i < intval($this->options["default"]); $i++) {
                $this->indices[$i] = $i + 1;
            }
        }
        return $this;
    }

    /**
     * Loops through the block
     * @return boolean
     */
    public function loop() {

        $manual = false;
        if(array_key_exists("manual", $this->options) && $this->options["manual"] == true) {
            $manual = true;
        }

        $this->setDefault();

        if ($this->current > 0) {
            if(!$manual) {
                $this->blockDestruct();
                $this->blockEnd();
            }
        }
        else {
            if(!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->options["limit"]) {
            if(!$manual) {
                $this->blockConstruct();
                $this->blockStart();
            }
            return true;
        }
        else {
            if(!$manual) {
                $this->end();
            }
            return false;
        }
    }
    
    /**
     * Alias for loop
     * @deprecated
     * @see loop()
     * @return boolean
     */
    public function enumerate() {
        return $this->loop();
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return void
     */
    public function start() {

        $this->setupStaticEnvironment();
        
        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        }
        else {
            $data = $this->getData();
        }

        $options = array(
            "options" => $this->getOptions(),
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType(),
            "inherited" => $this->getInherited()
        );
        $options = @\Zend_Json::encode($options);
        //$options = base64_encode($options);
        
        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');
        
        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        $suffixes[] = $this->getName();
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="pimcore_editable pimcore_tag_' . $this->getType() . '" type="' . $this->getType() . '">');

        return $this;
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     *
     * @return void
     */
    public function end() {

        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    /**
     *
     */
    public function blockConstruct () {

        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current];
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     *
     */
    public function blockDestruct () {
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @return void
     */
    public function blockStart() {

        $this->outputEditmode('<div class="pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons_' . $this->getName() . ' pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_amount_' . $this->getName() . ' pimcore_block_amount"></div>');
        $this->outputEditmode('<div class="pimcore_block_plus_' . $this->getName() . ' pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus_' . $this->getName() . ' pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up_' . $this->getName() . ' pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down_' . $this->getName() . ' pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');

        $this->current++;
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     *
     * @return void
     */
    public function blockEnd() {
        $this->outputEditmode('</div>');
    }

    /**
     * Sends data to the output stream
     *
     * @param string $v
     * @return void
     */
    public function outputEditmode($v) {
        if ($this->getEditmode()) {
            echo $v . "\n";
        }
    }

    /**
     * Setup some settings that are needed for blocks
     *
     * @return void
     */
    public function setupStaticEnvironment() {

        // setup static environment for blocks
        if(\Zend_Registry::isRegistered("pimcore_tag_block_current")) {
            $current = \Zend_Registry::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = array();
            }
        } else {
            $current = array();
        }

        if(\Zend_Registry::isRegistered("pimcore_tag_block_numeration")) {
            $numeration = \Zend_Registry::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = array();
            }
        } else {
            $numeration = array();
        }

        \Zend_Registry::set("pimcore_tag_block_numeration", $numeration);
        \Zend_Registry::set("pimcore_tag_block_current", $current);

    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options) {

        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Return the amount of block elements
     *
     * @return integer
     */
    public function getCount() {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return integer
     */
    public function getCurrent() {
        return $this->current-1;
    }
    
    /**
     * Return current index
     *
     * @return integer
     */
    public function getCurrentIndex () {
        return $this->indices[$this->getCurrent()];
    }

    /**
     * If object was serialized, set the counter back to 0
     *
     * @return void
     */
    public function __wakeup() {
        $this->current = 0;
    }
    
    /**
     * @return bool
     */
    public function isEmpty () {
        return !(bool) count($this->indices);
    }

    /**
     * @param Model\Document\Model\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null){
        $data = $wsElement->value;
        if(($data->indices === null or is_array($data->indices)) and ($data->current==null or is_numeric($data->current)) ){
            $this->indices = $data->indices;
            $this->current = $data->current;
        } else  {
            throw new \Exception("cannot get  values from web service import - invalid data");
        }


    }

    /**
     * @return Block\Item[]
     */
    public function getElements()
    {
        // init
        $doc = Model\Document\Page::getById( $this->getDocumentId() );

        $suffixes = (array)$this->suffixes;
        $suffixes[] = $this->getName();

        $list = array();
        foreach($this->getData() as $index)
        {
            $list[] = new Block\Item($doc, $index, $suffixes);
        }

        return $list;
    }
}
