<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Tool;
use Pimcore\Model;
use Pimcore\ExtensionManager;
use Pimcore\Model\Document;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Area extends Model\Document\Tag
{

    /**
     * @see Model\Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "area";
    }

    /**
     * @see Model\Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return null;
    }

    /**
     * @see Model\Document\Tag\TagInterface::admin
     */
    public function admin()
    {
        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        $options = [
            "options" => $this->getOptions(),
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType(),
            "inherited" => $this->getInherited()
        ];
        $options = @\Zend_Json::encode($options, false, ['enableJsonExprFinder' => true]);

        if ($this->editmode) {
            $class = "pimcore_editable pimcore_tag_" . $this->getType();
            if (array_key_exists("class", $this->getOptions())) {
                $class .= (" " . $this->getOptions()["class"]);
            }

            echo '
                <script type="text/javascript">
                    editableConfigurations.push(' . $options . ');
                </script>
                <div id="pimcore_editable_' . $this->getName() . '" class="' . $class . '">
            ';
        }


        $this->frontend();

        if ($this->editmode) {
            echo '</div>';
        }
    }

    /**
     * @see Model\Document\Tag\TagInterface::frontend
     */
    public function frontend()
    {
        $count = 0;
        $options = $this->getOptions();
        // don't show disabled bricks
        if (!ExtensionManager::isEnabled("brick", $options["type"]) && $options['dontCheckEnabled'] != true) {
            return;
        }

        $this->setupStaticEnvironment();
        $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        $suffixes[] = $this->getName();
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);


        $this->current = $count;


        // create info object and assign it to the view
        $info = null;
        try {
            $info = new Area\Info();
            $info->setTag($this);
            $info->setId($options["type"]);
            $info->setIndex($count);
            $info->setPath(str_replace(PIMCORE_DOCUMENT_ROOT, "", ExtensionManager::getPathForExtension($options["type"], "brick")));
            $info->setConfig(ExtensionManager::getBrickConfig($options["type"]));
        } catch (\Exception $e) {
            $info = null;
        }

        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = 1;
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

        if ($this->getView() instanceof \Zend_View) {
            $this->getView()->brick = $info;
            $areas = $this->getAreaDirs();

            $view = $areas[$options["type"]] . "/view.php";
            $action = $areas[$options["type"]] . "/action.php";
            $edit = $areas[$options["type"]] . "/edit.php";
            $options = $this->getOptions();
            $params = [];
            if (is_array($options["params"]) && array_key_exists($options["type"], $options["params"])) {
                if (is_array($options["params"][$options["type"]])) {
                    $params = $options["params"][$options["type"]];
                }
            }

            // assign parameters to view
            foreach ($params as $key => $value) {
                $this->getView()->assign($key, $value);
            }

            // check for action file
            if (is_file($action)) {
                include_once($action);


                $actionClassFound = true;

                $actionClass = preg_replace_callback("/[\-_][a-z]/", function ($matches) {
                    $replacement = str_replace(["-", "_"], "", $matches[0]);

                    return strtoupper($replacement);
                }, ucfirst($options["type"]));

                $actionClassname = "\\Pimcore\\Model\\Document\\Tag\\Area\\" . $actionClass;

                if (!Tool::classExists($actionClassname, false)) {
                    // also check the legacy prefixed class name, as this is used by some plugins
                    $actionClassname = "\\Document_Tag_Area_" . ucfirst($options["type"]);
                    if (!Tool::classExists($actionClassname, false)) {
                        $actionClassFound = false;
                    }
                }

                if ($actionClassFound) {
                    $actionObject = new $actionClassname();

                    if ($actionObject instanceof Area\AbstractArea) {
                        $actionObject->setView($this->getView());

                        $areaConfig = new \Zend_Config_Xml($areas[$options["type"]] . "/area.xml");
                        $actionObject->setConfig($areaConfig);

                        // params
                        $params = array_merge($this->view->getAllParams(), $params);
                        $actionObject->setParams($params);

                        if ($info) {
                            $actionObject->setBrick($info);
                        }

                        if (method_exists($actionObject, "action")) {
                            $actionObject->action();
                        }

                        $this->getView()->assign('actionObject', $actionObject);
                    }
                }
            }

            if (is_file($view)) {
                $editmode = $this->getView()->editmode;

                if (method_exists($actionObject, "getBrickHtmlTagOpen")) {
                    echo $actionObject->getBrickHtmlTagOpen($this);
                } else {
                    echo '<div class="pimcore_area_' . $options["type"] . ' pimcore_area_content">';
                }

                if (is_file($edit) && $editmode) {
                    echo '<div class="pimcore_area_edit_button"></div>';

                    // forces the editmode in view.php independent if there's an edit.php or not
                    if (!array_key_exists("forceEditInView", $params) || !$params["forceEditInView"]) {
                        $this->getView()->editmode = false;
                    }
                }

                $this->getView()->template($view);

                if (is_file($edit) && $editmode) {
                    $this->getView()->editmode = true;

                    echo '<div class="pimcore_area_editmode pimcore_area_editmode_hidden">';
                    $this->getView()->template($edit);
                    echo '</div>';
                }

                if (method_exists($actionObject, "getBrickHtmlTagClose")) {
                    echo $actionObject->getBrickHtmlTagClose($this);
                } else {
                    echo '</div>';
                }


                if (is_object($actionObject) && method_exists($actionObject, "postRenderAction")) {
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
     * @return $this
     */
    public function setDataFromResource($data)
    {
        return $this;
    }

    /**
     * @see Model\Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        return $this;
    }

    /**
     * Setup some settings that are needed for blocks
     *
     * @return void
     */
    public function setupStaticEnvironment()
    {

        // setup static environment for blocks
        if (\Zend_Registry::isRegistered("pimcore_tag_block_current")) {
            $current = \Zend_Registry::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = [];
            }
        } else {
            $current = [];
        }

        if (\Zend_Registry::isRegistered("pimcore_tag_block_numeration")) {
            $numeration = \Zend_Registry::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = [];
            }
        } else {
            $numeration = [];
        }

        \Zend_Registry::set("pimcore_tag_block_numeration", $numeration);
        \Zend_Registry::set("pimcore_tag_block_current", $current);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getAreaDirs()
    {
        return ExtensionManager::getBrickDirectories();
    }

    public function getBrickConfigs()
    {
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
        $doc = Model\Document\Page::getById($this->getDocumentId());
        $id = sprintf('%s%s%d', $name, $this->getName(), 1);
        $element = $doc->getElement($id);
        if ($element) {
            $element->suffixes = [ $this->getName() ];
        }

        return $element;
    }
}
