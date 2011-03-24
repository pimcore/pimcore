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

    public function tag($type, $name, $options = array()) {

        try {
            // @todo add document-id to registry key | for example for embeded snippets
            // set suffixes if the tag is inside a block
            try {
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
            catch (Exception $e) {
            }

            $document = $this->document;
            
            if($document instanceof Document) {
                $tag = $document->getElement($name);
                if ($tag instanceof Document_Tag && $tag->getType() == $type) {

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

    // includes a template
    public function template($scriptPath, $params = array()) {

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
    }

    // includes a document
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

        if ($include instanceof Document && $include->isPublished()) {
            if ($include->getTemplate()) {
                return $this->action("default", "default", null, $params);
            }

            if ($include->getAction() && $include->getController()) {
                return $this->action($include->getAction(), $include->getController(), null, $params);
            }
        }

        Zend_Registry::set("pimcore_editmode", $editmodeBackup);
        
        return "";
    }
    
    public function getParam($key) {
        if(is_array($this->params)) {
            return $this->params[$key];
        }
    }
    
    public function getAllParams () {
        return $this->params;
    }

    public function _getParam($key) {
        return $this->getParam($key);
    }

    public function _getAllParams () {
        return $this->getAllParams();
    }

    public function __call($method, $arguments) {

        $class = "Document_Tag_" . ucfirst(strtolower($method));
        $tagFile = str_replace("_", "/", $class) . ".php";

        if (is_includeable($tagFile)) {
            include_once($tagFile);
            if (@class_exists($class)) {
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
}
