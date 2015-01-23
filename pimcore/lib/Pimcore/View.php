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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

namespace Pimcore;

use Pimcore\Model;
use Pimcore\Model\Element;

class View extends \Zend_View {

    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * @var bool
     */
    protected static $addComponentIds = false;

    /**
     * @param $type
     * @param $realName
     * @param array $options
     * @return Model\Document\Tag
     */
    public function tag($type, $realName, $options = array()) {

        $type = strtolower($type);

        try {
            $document = $this->document;
            $name = Model\Document\Tag::buildTagName($type,$realName, $document);

            if($document instanceof Model\Document) {
                $tag = $document->getElement($name);
                if ($tag instanceof Model\Document\Tag && $tag->getType() == $type) {

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
                    $tag = Model\Document\Tag::factory($type, $name, $document->getId(), $options, $this->controller, $this, $this->editmode);
                    $document->setElement($name, $tag);
                }

                // set the real name of this editable, without the prefixes and suffixes from blocks and areablocks
                $tag->setRealName($realName);
            }
            
            return $tag;
        }
        catch (\Exception $e) {
            \Logger::warning($e);
        }
    }

    /**
     * @param string $script
     */
    public function includeTemplateFile($script){
        $showTemplatePaths = isset($_REQUEST["pimcore_show_template_paths"]);
        if($showTemplatePaths && \Pimcore::inDebugMode()){
            echo "\n<!-- start template inclusion: " . $script . " -->\n";
        }
        include($script);
        if($showTemplatePaths && \Pimcore::inDebugMode()){
            echo "\n<!-- finished template inclusion: " . $script . " -->\n";
        }
    }

    /**
     * @param $scriptPath
     * @param array $params
     * @param bool $resetPassedParams
     * @param bool $capture
     * @return string
     */
    public function template($scriptPath, $params = array(), $resetPassedParams = false, $capture = false) {

        foreach ($params as $key => $value) {
            $this->assign($key, $value);
        }

        if($capture){
            $captureKey = (is_string($capture)) ? $capture : 'pimcore_capture_template';
            $this->placeholder($captureKey)->captureStart(\Zend_View_Helper_Placeholder_Container_Abstract::SET);
        }

        $found = false;
        $paths = $this->getScriptPaths();
        $paths[] = PIMCORE_DOCUMENT_ROOT;

        foreach ($paths as $path) {
            $p = $path . $scriptPath;
            if (is_file($p) && !$found) {
                $found = true;
                $this->includeTemplateFile($p);
                break;
            }
        }

        if(!$found) {
            if(is_file($scriptPath)) {
                $found = true;
                $this->includeTemplateFile($scriptPath);
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
    public function inc($include, $params = null, $cacheEnabled = true) {

        if(!is_array($params)) {
            $params = [];
        }

        // check if output-cache is enabled, if so, we're also using the cache here
        $cacheKey = null;
        $cacheConfig = false;

        if($cacheEnabled) {
            if($cacheConfig = Tool\Frontend::isOutputCacheEnabled()) {

                // cleanup params to avoid serializing Element\ElementInterface objects
                $cacheParams = $params;
                $cacheParams["~~include-document"] = $include;
                array_walk($cacheParams, function (&$value, $key) {
                    if($value instanceof Element\ElementInterface) {
                        $value = $value->getId();
                    } else if (is_object($value) && method_exists($value, "__toString")) {
                        $value = (string) $value;
                    }
                });

                $cacheKey = "tag_inc__" . md5(serialize($cacheParams));
                if($content = Model\Cache::load($cacheKey)) {
                    return $content;
                }
            }
        }


        $editmodeBackup = \Zend_Registry::get("pimcore_editmode");
        \Zend_Registry::set("pimcore_editmode", false);

        $includeBak = $include;

        // this is if $this->inc is called eg. with $this->href() as argument
        if(!$include instanceof Model\Document\PageSnippet && is_object($include) && method_exists($include, "__toString")) {
            $include = (string) $include;
        }

        if (is_string($include)) {
            try {
                $include = Model\Document::getByPath($include);
            }
            catch (\Exception $e) {
                $include = $includeBak;
            }
        }
        else if (is_numeric($include)) {
            try {
                $include = Model\Document::getById($include);
            }
            catch (\Exception $e) {
                $include = $includeBak;
            }
        }

        $params = array_merge($params, array("document" => $include));
        $content = "";

        if ($include instanceof Model\Document\PageSnippet && $include->isPublished()) {
            if ($include->getAction() && $include->getController()) {
                $content = $this->action($include->getAction(), $include->getController(), $include->getModule(), $params);
            } else if ($include->getTemplate()) {
                $content = $this->action("default", "default", null, $params);
            }

            // in editmode, we need to parse the returned html from the document include
            // add a class and the pimcore id / type so that it can be opened in editmode using the context menu
            // if there's no first level HTML container => add one (wrapper)
            if($this->editmode) {

                include_once("simple_html_dom.php");

                $editmodeClass = " pimcore_editable pimcore_tag_inc ";

                // this is if the content that is included does already contain markup/html
                // this is needed by the editmode to highlight included documents
                if($html = str_get_html($content)) {
                    $childs = $html->find("*");
                    if(is_array($childs)) {
                        foreach ($childs as $child) {
                            $child->class = $child->class . $editmodeClass;
                            $child->pimcore_type = $include->getType();
                            $child->pimcore_id = $include->getId();
                        }
                    }
                    $content = $html->save();

                    $html->clear();
                    unset($html);
                } else {
                    // add a div container if the include doesn't contain markup/html
                    $content = '<div class="' . $editmodeClass . '" pimcore_id="' . $include->getId() . '" pimcore_type="' . $include->getType() . '">' . $content . '</div>';
                }
            }

            // we need to add a component id to all first level html containers
            $componentId = "";
            if($this->document instanceof Model\Document) {
                $componentId .= 'document:' . $this->document->getId() . '.';
            }
            $componentId .= 'type:inc.name:' . $include->getId();;
            $content = \Pimcore\Tool\Frontend::addComponentIdToHtml($content, $componentId);
        }

        \Zend_Registry::set("pimcore_editmode", $editmodeBackup);

        // write contents to the cache, if output-cache is enabled
        if($cacheConfig) {
            Model\Cache::save($content, $cacheKey, array("output"), $cacheConfig["lifetime"]);
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
     * @param $key
     * @param null $default
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
     * @return \Zend_Controller_Request_Http
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request) {
        $this->request = $request;
        return $this;
    }

    /**
     * shorthand for $this->translate() view helper
     */
    public function t() {
        return call_user_func_array(array($this, "translate"), func_get_args());
    }

    /**
     * shorthand for $this->translateAdmin() view helper
     */
    public function ts() {
        return call_user_func_array(array($this, "translateAdmin"), func_get_args());
    }

    /**
     * @param string $method
     * @param array $arguments
     * @return mixed|Model\Document\Tag|string
     * @throws \Exception
     */
    public function __call($method, $arguments) {

        $class = "\\Pimcore\\Model\\Document\\Tag\\" . ucfirst(strtolower($method));
        $tagFile = "Document/Tag/" . ucfirst(strtolower($method)) . ".php";

        if (File::isIncludeable($tagFile)) {
            include_once($tagFile);

            $classFound = true;
            if(!\Pimcore\Tool::classExists($class)) {
                $oldStyleClass = "Document_Tag_" . ucfirst(strtolower($method));
                if(!\Pimcore\Tool::classExists($oldStyleClass)) {
                    $classFound = false;
                }
            }

            if ($classFound) {
                if(!isset($arguments[0])) {
                    throw new \Exception ("You have to set a name for the called tag (editable): " . $method);
                }

                // set default if there is no editable configuration provided
                if(!isset($arguments[1])) {
                    $arguments[1] = array();
                }
                return $this->tag($method, $arguments[0], $arguments[1]);
            }
        }

        if ($this->document instanceof Model\Document) {
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
        if(\Zend_Controller_Front::getInstance()->getRequest()->getModuleName() == PIMCORE_FRONTEND_MODULE) {
            $customViewSuffix = Config::getSystemConfig()->general->viewSuffix;
            if(!empty($customViewSuffix)) {
                $viewSuffix = $customViewSuffix;
            }
        }

        return $viewSuffix;
    }

    /**
     * @return boolean
     */
    public static function addComponentIds()
    {
        return self::$addComponentIds;
    }

    /**
     * @param boolean $addComponentIds
     */
    public static function setAddComponentIds($addComponentIds)
    {
        self::$addComponentIds = $addComponentIds;
    }

}
