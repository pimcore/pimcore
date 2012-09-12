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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Pimcore_View extends Zend_View {

    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $request;


    /**
     * @param $type
     * @param $name
     * @param array $options
     * @return Tag
     */
    public function tag($type, $name, $options = array()) {

        $type = strtolower($type);

        try {
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

            $document = $this->document;
            
            if($document instanceof Document) {
                $tag = $document->getElement($name);
                if ($tag instanceof Document_Tag && $tag->getType() == $type) {

                    // call the load() method if it exists to reinitialize the data (eg. from serializing, ...)
                    if(method_exists($tag, "load")) {
                        $tag->load();
                    }

                    // set view & controller, editmode
                    $tag->setController($this->controller);
                    $tag->setView($this);
                    $tag->setEditmode($this->editmode);
                    
                    $tag->setOptions($options);
                }
                else {
                    $tag = Document_Tag::factory($type, $name, $document->getId(), $options, $this->controller, $this, $this->editmode);
                    $document->setElement($name, $tag);
                }
            }
            
            return $tag;
        }
        catch (Exception $e) {
            Logger::warning($e);
        }
    }

    /**
     * includes a template
     *
     * @param $scriptPath
     * @param array $params
     * @return void
     */
    public function template($scriptPath, $params = array(), $resetPassedParams = false) {

        foreach ($params as $key => $value) {
            $this->assign($key, $value);
        }
        
        $found = false;
        $paths = $this->getScriptPaths();
        foreach ($paths as $path) {
            $p = $path . $scriptPath;
            if (is_file($p) && !$found) {
                $found = true;
                include($p);
                
                break;
            }
        }
        
        if(!$found) {
            if(is_file($scriptPath)) {
                $found = true;
                include($scriptPath);
            }
        }

        if($resetPassedParams){
            foreach($params as $key => $value){
                $this->$key = null;
            }
        }
    }

    /**
     * includes a document
     *
     * @param $include
     * @param array $params
     * @return string
     */
    public function inc($include, $params = array()) {

        $editmodeBackup = Zend_Registry::get("pimcore_editmode");
        Zend_Registry::set("pimcore_editmode", false);

        $includeBak = $include;

        if (is_string($include)) {
            try {
                $include = Document::getByPath($include);
            }
            catch (Exception $e) {
                $include = $includeBak;
            }
        }
        else if (is_numeric($include)) {
            try {
                $include = Document::getById($include);
            }
            catch (Exception $e) {
                $include = $includeBak;
            }
        }

        $params = array_merge($params, array("document" => $include));
        $content = "";

        if ($include instanceof Document_PageSnippet && $include->isPublished()) {
            if ($include->getAction() && $include->getController()) {
                $content = $this->action($include->getAction(), $include->getController(), $include->getModule(), $params);
            } else if ($include->getTemplate()) {
                $content = $this->action("default", "default", null, $params);
            }

            // in editmode add events at hover an click to be able to edit the included document
            if($this->editmode) {

                include_once("simple_html_dom.php");

                $class = " pimcore_editable pimcore_tag_inc ";

    
                // this is if the content if the include does already contain markup/html
                if($html = str_get_html($content)) {
                    $childs = $html->find("*");
                    if(is_array($childs)) {
                        foreach ($childs as $child) {
                            $child->class = $child->class . $class;
                            $child->pimcore_type = $include->getType();
                            $child->pimcore_id = $include->getId();
                        }
                    }
                    $content = $html->save();
                } else {
                    // add a div container if the include doesn't contain markup/html
                    $content = '<div class="' . $class . '">' . $content . '</div>';
                }
            }
        }

        Zend_Registry::set("pimcore_editmode", $editmodeBackup);

        return $content;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    public function getParam($key, $default = null) {
        $value = $this->getRequest()->getParam($key);
        if ((null === $value || '' === $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * @deprecated
     * @param $key
     * @return mixed
     */
    public function _getParam($key, $default = null) {
        return $this->getParam($key, $default);
    }

    /**
     * @return array
     */
    public function getAllParams () {
        return $this->getRequest()->getParams();
    }

    /**
     * @deprecated
     * @return array
     */
    public function _getAllParams () {
        return $this->getAllParams();
    }

    /**
     * @return Zend_Controller_Request_Http
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param Zend_Controller_Request_Http $request
     * @return void
     */
    public function setRequest(Zend_Controller_Request_Http $request) {
        $this->request = $request;
    }

    /**
     * @throws Exception
     * @param $method
     * @param $arguments
     * @return mixed|string|Tag
     */
    public function __call($method, $arguments) {

        $class = "Document_Tag_" . ucfirst(strtolower($method));
        $tagFile = str_replace("_", "/", $class) . ".php";

        if (Pimcore_File::isIncludeable($tagFile)) {
            include_once($tagFile);
            if (@Pimcore_Tool::classExists($class)) {
                if(!isset($arguments[0])) {
                    throw new Exception ("You have to set a name for the called tag (editable): " . $method);
                }

                // set default if there is no editable configuration provided
                if(!isset($arguments[1])) {
                    $arguments[1] = array();
                }
                return $this->tag($method, $arguments[0], $arguments[1]);
            }
        }

        if ($this->document instanceof Document) {
            if (method_exists($this->document, $method)) {
                return call_user_func_array(array($this->document, $method), $arguments);
            }
        }

        return parent::__call($method, $arguments);
    }

    /**
     * @static
     * @return string
     */
    public static function getViewScriptSuffix () {
        // default is php
        $viewSuffix = "php";

        // custom view suffixes are only available for the frontend module (website)
        if(Zend_Controller_Front::getInstance()->getRequest()->getModuleName() == PIMCORE_FRONTEND_MODULE) {
            $customViewSuffix = Pimcore_Config::getSystemConfig()->general->viewSuffix;
            if(!empty($customViewSuffix)) {
                $viewSuffix = $customViewSuffix;
            }
        }

        return $viewSuffix;
    }
}
