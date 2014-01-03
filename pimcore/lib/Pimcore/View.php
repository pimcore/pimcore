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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
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
            $document = $this->document;
            $name = Document_Tag::buildTagName($type,$name, $document);

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
     * @return void | string
     */
    public function template($scriptPath, $params = array(), $resetPassedParams = false, $capture = false) {

        foreach ($params as $key => $value) {
            $this->assign($key, $value);
        }

        if($capture){
            $captureKey = (is_string($capture)) ? $capture : 'pimcore_capture_template';
            $this->placeholder($captureKey)->captureStart(Zend_View_Helper_Placeholder_Container_Abstract::SET);
        }

        $found = false;
        $paths = $this->getScriptPaths();
        $paths[] = PIMCORE_DOCUMENT_ROOT;

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

        if($capture){
            $this->placeholder($captureKey)->captureEnd();
            return trim($this->placeholder($captureKey)->getValue());
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

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey = null;
        if($cacheConfig = Pimcore_Tool_Frontend::isOutputCacheEnabled()) {

            // cleanup params to avoid serializing Element_Interface objects
            $cacheParams = $params;
            $cacheParams["~~include-document"] = $include;
            array_walk($cacheParams, function (&$value, $key) {
                if($value instanceof Element_Interface) {
                    $value = $value->getId();
                } else if (is_object($value) && method_exists($value, "__toString")) {
                    $value = (string) $value;
                }
            });

            $cacheKey = "tag_inc__" . md5(serialize($cacheParams));
            if($content = Pimcore_Model_Cache::load($cacheKey)) {
                return $content;
            }
        }


        $editmodeBackup = Zend_Registry::get("pimcore_editmode");
        Zend_Registry::set("pimcore_editmode", false);

        $includeBak = $include;

        // this is if $this->inc is called eg. with $this->href() as argument
        if(!$include instanceof Document_PageSnippet && is_object($include) && method_exists($include, "__toString")) {
            $include = (string) $include;
        }

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

    
                // this is if the content that is included does already contain markup/html
                // this is needed by the editmode to highlight included documents
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

                    $html->clear();
                    unset($html);
                } else {
                    // add a div container if the include doesn't contain markup/html
                    $content = '<div class="' . $class . '">' . $content . '</div>';
                }
            }
        }

        Zend_Registry::set("pimcore_editmode", $editmodeBackup);

        // write contents to the cache, if output-cache is enabled
        if($cacheConfig) {
            Pimcore_Model_Cache::save($content, $cacheKey, array("output"), $cacheConfig["lifetime"]);
        }

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
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function setRequest(Zend_Controller_Request_Abstract $request) {
        $this->request = $request;
        return $this;
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
