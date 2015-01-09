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
use Pimcore\ExtensionManager;
use Pimcore\Model\Document;

class Area extends Model\Document\Tag {

    /**
     * @see Model\Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "area";
    }

    /**
     * @see Model\Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return null;
    }

    /**
     * @see Model\Document\Tag\TagInterface::admin
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
        $options = @\Zend_Json::encode($options, false, array('enableJsonExprFinder' => true));

        if($this->editmode) {
            echo '
                <script type="text/javascript">
                    editableConfigurations.push(' . $options . ');
                </script>
                <div id="pimcore_editable_' . $this->getName() . '" class="pimcore_editable pimcore_tag_' . $this->getType() . '">
            ';
        }


        $this->frontend();

        if($this->editmode) {
            echo '</div>';
        }

    }

    /**
     * @see Model\Document\Tag\TagInterface::frontend
     */
    public function frontend() {

        $count = 0;

        $this->setupStaticEnvironment();
        $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        $suffixes[] = $this->getName();
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $options = $this->getOptions();

        $this->current = $count;

        // don't show disabled bricks
        if(!ExtensionManager::isEnabled("brick", $options["type"]) && $options['dontCheckEnabled'] != true) {
            return;
        }

        // create info object and assign it to the view
        $info = null;
        try {
            $info = new Area\Info();
            $info->setTag($this);
            $info->setId($options["type"]);
            $info->setIndex($count);
            $info->setPath(str_replace(PIMCORE_DOCUMENT_ROOT, "", ExtensionManager::getPathForExtension($options["type"],"brick")));
            $info->setConfig(ExtensionManager::getBrickConfig($options["type"]));
        } catch (\Exception $e) {
            $info = null;
        }

        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = 1;
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

        if($this->getView() instanceof \Zend_View) {

            $this->getView()->brick = $info;
            $areas = $this->getAreaDirs();

            $view = $areas[$options["type"]] . "/view.php";
            $action = $areas[$options["type"]] . "/action.php";
            $edit = $areas[$options["type"]] . "/edit.php";
            $options = $this->getOptions();
            $params = array();
            if(is_array($options["params"]) && array_key_exists($options["type"], $options["params"])) {
                if(is_array($options["params"][$options["type"]])) {
                    $params = $options["params"][$options["type"]];
                }
            }

            // assign parameters to view
            foreach ($params as $key => $value) {
                $this->getView()->assign($key, $value);
            }

            // check for action file
            if(is_file($action)) {
                include_once($action);

                $actionClassname = "\\Pimcore\\Model\\Document\\Tag\\Area\\" . ucfirst($options["type"]);
                if(\Pimcore\Tool::classExists($actionClassname)) {
                    $actionObject = new $actionClassname();

                    if($actionObject instanceof Area\AbstractArea) {
                        $actionObject->setView($this->getView());

                        $areaConfig = new \Zend_Config_Xml($areas[$options["type"]] . "/area.xml");
                        $actionObject->setConfig($areaConfig);

                        // params
                        $params = array_merge($this->view->getAllParams(), $params);
                        $actionObject->setParams($params);

                        if($info) {
                            $actionObject->setBrick($info);
                        }

                        if(method_exists($actionObject,"action")) {
                            $actionObject->action();
                        }

                        $this->getView()->assign('actionObject',$actionObject);
                    }
                }
            }

            if(is_file($view)) {
                $editmode = $this->getView()->editmode;

                if(method_exists($actionObject,"getBrickHtmlTagOpen")) {
                    echo $actionObject->getBrickHtmlTagOpen($this);
                }else{
                    echo '<div class="pimcore_area_' . $options["type"] . ' pimcore_area_content">';
                }

                if(is_file($edit) && $editmode) {
                    echo '<div class="pimcore_area_edit_button"></div>';

                    // forces the editmode in view.php independent if there's an edit.php or not
                    if(!array_key_exists("forceEditInView",$params) || !$params["forceEditInView"]) {
                        $this->getView()->editmode = false;
                    }
                }

                $this->getView()->template($view);

                if(is_file($edit) && $editmode) {
                    $this->getView()->editmode = true;

                    echo '<div class="pimcore_area_editmode pimcore_area_editmode_hidden">';
                    $this->getView()->template($edit);
                    echo '</div>';
                }

                if(method_exists($actionObject,"getBrickHtmlTagClose")) {
                    echo $actionObject->getBrickHtmlTagClose($this);
                }else{
                    echo '</div>';
                }


                if(is_object($actionObject) && method_exists($actionObject,"postRenderAction")) {
                    $actionObject->postRenderAction();
                }
            }
        }

        
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
        
        $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);
    }

    /**
     * @see Model\Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        return $this;
    }

    /**
     * @see Model\Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        return $this;
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
     * @return bool
     */
    public function isEmpty () {
        return false;
    }

    /**
     * @return array
     */
    public function getAreaDirs () {
        return ExtensionManager::getBrickDirectories();
    }

    public function getBrickConfigs() {
        return ExtensionManager::getBrickConfigs();
    }

    /**
     * @param $name
     *
     * @return Model\Document\Tag
     */
    public function getElement($name)
    {
        // init
        $doc = Model\Document\Page::getById( $this->getDocumentId() );
        $id = sprintf('%s%s%d', $name, $this->getName(), 1);
        $element = $doc->getElement( $id );
        $element->suffixes = array( $this->getName() );

        return $element;
    }
}
