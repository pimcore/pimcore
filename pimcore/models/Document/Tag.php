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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

abstract class Document_Tag extends Pimcore_Model_Abstract implements Document_Tag_Interface {

    /**
     * Options of the current tag, can contain some configurations for the editmode, or the thumbnail name, ...
     *
     * @var array
     */
    protected $options;

    /**
     * @var string
     */
    protected $name;

    /**
     * Element belongs to the ID of the document
     *
     * @var integer
     */
    protected $documentId;

    /**
     * Resource of the tag
     *
     * @var mixed
     */
    protected $resource;

    /**
     * @var Pimcore_Controller_Action
     */
    protected $controller;

    /**
     * @var Pimcore_View
     */
    protected $view;

    /**
     * In Editmode or not
     *
     * @var boolean
     */
    protected $editmode;

    /**
     * @var bool
     */
    protected $inherited = false;

    /**
     * @param string $type
     * @param string $name
     * @param integer $documentId
     * @param array $config
     * @return Tag
     */
    public static function factory($type, $name, $documentId, $config = null, $controller = null, $view = null, $editmode = null) {

        $tagClass = "Document_Tag_" . ucfirst($type);
        $tag = new $tagClass();
        $tag->setName($name);
        $tag->setDocumentId($documentId);
        $tag->setController($controller);
        $tag->setView($view);
        $tag->setEditmode($editmode);
        $tag->setOptions($config);
        return $tag;
    }

    /**
     * @see Document_Tag_Interface::admin
     * @return string
     */
    public function admin() {

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
        $options = @Zend_Json::encode($options, false, array('enableJsonExprFinder' => true));

        return '
            <script type="text/javascript">
                editableConfigurations.push(' . $options . ');
            </script>
            <div id="pimcore_editable_' . $this->getName() . '" class="pimcore_editable pimcore_tag_' . $this->getType() . '"></div>
        ';
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getValue() {
        return $this->getData();
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * @param integer $id
     * @return void
     */
    public function setDocumentId($id) {
        $this->documentId = (int) $id;
        return $this;
    }

    /**
     * @return integer
     */
    public function getDocumentId() {
        return $this->documentId;
    }

    /**
     * @return array
     */
    public function getOptions() {
        return is_array($this->options) ? $this->options : array();
    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options) {
        $this->options = $options;
        return $this;
    }

    /**
     * @param Pimcore_Controller_Action $controller
     * @return void
     */
    public function setController($controller) {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return Pimcore_Controller_Action
     */
    public function getController() {
        return $this->controller;
    }

    /**
     * @param Pimcore_View $view
     * @return void
     */
    public function setView($view) {
        $this->view = $view;
        return $this;
    }

    /**
     * @return Pimcore_View
     */
    public function getView() {
        return $this->view;
    }


    /**
     * Retourns only the properties which should be serialized
     *
     * @return void
     */
    public function __sleep() {
        $blockedVars = array("resource", "controller", "view", "editmode");
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }
        return $finalVars;
    }

    /**
     * direct output to the frontend
     *
     * @return string
     */
    public function __toString() {
        try {
            if ($this->editmode) {
                $return = $this->admin();
            }
            else {
                $return = $this->frontend();
            }
        } catch (Exception $e) {
            Logger::error("to string not possible - " . $e->getMessage());
        }

        if (is_string($return)) {
            return $return;
        }
        return '';

    }


    /**
     * @return boolean
     */
    public function getEditmode() {
        return $this->editmode;
    }

    /**
     * @param boolean $editmode
     * @return void
     */
    public function setEditmode($editmode) {
        $this->editmode = (bool) $editmode;
        return $this;
    }


    /**
     * @see Document_Tag_Interface::getDataForResource
     * @return void
     */
    public function getDataForResource() {
        $this->checkValidity();
        return $this->getData();
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     * @param $ownerDocument
     * @param array $blockedTags
     */
    public function getCacheTags($ownerDocument, $tags = array()) {
        return $tags;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     */
    public function resolveDependencies() {
        return array();
    }


    /**
     * Receives a standard class object from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $wsElement
     * @return void
     */
    public function getFromWebserviceImport($wsElement) {
        return $wsElement;
    }


    /**
     * Returns the current tag's data for web service export
     *
     * @abstract
     * @return array
     */
    public function getForWebserviceExport() {
        $keys = get_object_vars($this);

        $el = array();
        foreach ($keys as $key => $value) {

            if ($value instanceof Element_Interface) {
                $value = $value->getId();
            }
            $className = Webservice_Data_Mapper::findWebserviceClass($value,"out");
            $el[$key] = Webservice_Data_Mapper::map($value,$className,"out");
        }

        unset($el["resource"]);
        unset($el["documentId"]);
        unset($el["controller"]);
        unset($el["view"]);
        unset($el["editmode"]);

        $el = Webservice_Data_Mapper::toObject($el);
        return $el;
    }



    /**
     * @return bool
     */
    public function checkValidity() {
        return true; 
    }

    /**
     * @param boolean $inherited
     */
    public function setInherited($inherited)
    {
        $this->inherited = $inherited;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getInherited()
    {
        return $this->inherited;
    }

    /**
     * Creates the Tag name for an element - must be called at runtime because of the block numeration
     * @param $type
     * @param $name
     *
     * @return string
     */
    public static function buildTagName($type,$name, $document = null){

        // check for persona content
        if($document && $document instanceof Document_Page && $document->getUsePersona()) {
            $name = $document->getPersonaElementName($name);
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the tag is inside a block
        if(Zend_Registry::isRegistered("pimcore_tag_block_current")) {
            $blocks = Zend_Registry::get("pimcore_tag_block_current");

            $numeration = Zend_Registry::get("pimcore_tag_block_numeration");
            if (is_array($blocks) and count($blocks) > 0) {

                if ($type == "block") {
                    $tmpBlocks = $blocks;
                    $tmpNumeration = $numeration;
                    array_pop($tmpBlocks);
                    array_pop($tmpNumeration);

                    $tmpName = $name;
                    if (is_array($tmpBlocks)) {
                        $tmpName = $name . implode("_", $tmpBlocks) . implode("_", $tmpNumeration);
                    }

                    if ($blocks[count($blocks) - 1] == $tmpName) {
                        array_pop($blocks);
                        array_pop($numeration);
                    }

                }
                $name = $name . implode("_", $blocks) . implode("_", $numeration);
            }
        }

        return $name;
    }




}
