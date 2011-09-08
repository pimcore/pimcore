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
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */

class Document_Tag_Areablock extends Document_Tag {

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
     * @see Document_Tag_Interface::getType
     * @return string
     */
    public function getType() {
        return "areablock";
    }

    /**
     * @see Document_Tag_Interface::getData
     * @return mixed
     */
    public function getData() {
        return $this->indices;
    }

    /**
     * @see Document_Tag_Interface::admin
     */
    public function admin() {
        $this->frontend();
    }

    /**
     * @see Document_Tag_Interface::frontend
     */
    public function frontend() {
        $count = 0;
        $this->start();
        $options = $this->getOptions();
        foreach ($this->indices as $index) {
            
            $this->current = $count;

            // don't show disabled bricks
            if(!Pimcore_ExtensionManager::isEnabled("brick", $index["type"]) && $options['dontCheckEnabled'] != true) {
                $count++;
                continue;
            }

            if($count > 0) {
                $this->blockEnd();
            }

            // create info object and assign it to the view
            $info = null;
            try {
                $info = new Document_Tag_Area_Info();
                $info->setId($index["type"]);
                $info->setIndex($count);
                $info->setPath(str_replace(PIMCORE_DOCUMENT_ROOT, "", Pimcore_ExtensionManager::getPathForExtension($index["type"],"brick")));
                $info->setConfig(Pimcore_ExtensionManager::getBrickConfig($index["type"]));
            } catch (Exception $e) {
                $info = null;
            }

            $this->blockStart();
            
            if($this->getView() instanceof Zend_View) {

                $this->getView()->brick = $info;
                $areas = $this->getAreaDirs();
                
                $view = $areas[$index["type"]] . "/view.php";
                $action = $areas[$index["type"]] . "/action.php";
                $edit = $areas[$index["type"]] . "/edit.php";
                $options = $this->getOptions();
                $params = array();
                if(is_array($options["params"]) && array_key_exists($index["type"], $options["params"])) {
                    if(is_array($options["params"][$index["type"]])) {
                        $params = $options["params"][$index["type"]];
                    }
                }

                // assign parameters to view
                foreach ($params as $key => $value) {
                    $this->getView()->assign($key, $value);
                }

                // check for action file
                if(is_file($action)) {
                    include_once($action);
                    
                    $actionClassname = "Document_Tag_Area_" . ucfirst($index["type"]);
                    if(class_exists($actionClassname)) {
                        $actionObj = new $actionClassname();
                        
                        if($actionObj instanceof Document_Tag_Area_Abstract) {
                            $actionObj->setView($this->getView());
                            
                            $areaConfig = new Zend_Config_Xml($areas[$index["type"]] . "/area.xml");
                            $actionObj->setConfig($areaConfig);

                            // params
                            $params = array_merge($this->view->getAllParams(), $params);
                            $actionObj->setParams($params);

                            if($info) {
                                $actionObj->setBrick($info);
                            }

                            if(method_exists($actionObj,"action")) {
                                $actionObj->action();
                            }
                        }
                    }
                }
                
                if(is_file($view)) {
                    $editmode = $this->getView()->editmode;
                    
                    echo '<div class="pimcore_area_' . $index["type"] . ' pimcore_area_content">';

                    if(is_file($edit) && $editmode) {
                        echo '<div class="pimcore_area_edit_button"></div>';
                        $this->getView()->editmode = false;
                    }

                    $this->getView()->template($view);

                    if(is_file($edit) && $editmode) {
                        $this->getView()->editmode = true; 

                        echo '<div class="pimcore_area_editmode">';
                        $this->getView()->template($edit);
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
            }            
            
            $count++;
        }
        
        if(count($this->indices) > 0) {
            $this->blockEnd();
        }
        
        $this->end();
    }

    /**
     * @see Document_Tag_Interface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        $this->indices = unserialize($data);
    }

    /**
     * @see Document_Tag_Interface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        $this->indices = $data;
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
            "type" => $this->getType()
        );
        $options = @Zend_Json::encode($options);
        //$options = base64_encode($options);
        
        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');
        
        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = Zend_Registry::get("pimcore_tag_block_current");
        $suffixes[] = $this->getName();
        Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="pimcore_editable pimcore_tag_' . $this->getType() . '" type="' . $this->getType() . '">');
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     *
     * @return void
     */
    public function end() {

        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = Zend_Registry::get("pimcore_tag_block_current");
        array_pop($suffixes);
        Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @return void
     */
    public function blockStart() {

        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current]["key"];
        Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

        $this->outputEditmode('<div class="pimcore_area_entry pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current]["key"] . '" type="' . $this->indices[$this->current]["type"] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_type"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     *
     * @return void
     */
    public function blockEnd() {

        $suffixes = Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);

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
        try {
            $current = Zend_Registry::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = array();
            }
        }
        catch (Exception $e) {
            $current = array();
        }

        try {
            $numeration = Zend_Registry::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = array();
            }
        }
        catch (Exception $e) {
            $numeration = array();
        }

        Zend_Registry::set("pimcore_tag_block_numeration", $numeration);
        Zend_Registry::set("pimcore_tag_block_current", $current);

    }

    /**
     * @param array $options
     * @return void
     */
    public function setOptions($options) {
               
        // read available types
        $areaConfigs = $this->getBrickConfigs();
        $availableAreas = array();
        $availableAreasSort = array();
        
        if(!is_array($options["allowed"])) {
            $options["allowed"] = array();
        }
        
        foreach ($areaConfigs as $areaName => $areaConfig) {

            // don't show disabled bricks
            if(!$options['dontCheckEnabled']){
                if(!Pimcore_ExtensionManager::isEnabled("brick", $areaName)) {
                     continue;
                }
            }


            if(empty($options["allowed"]) || in_array($areaName,$options["allowed"])) {

                $n = (string) $areaConfig->name;
                $d = (string) $areaConfig->description;
                if($this->view){
                    $n = $this->view->translateAdmin((string) $areaConfig->name);
                    $d = $this->view->translateAdmin((string) $areaConfig->description);
                }

                $availableAreas[] = array(
                    "name" => $n,
                    "description" => $d,
                    "type" => $areaName
                );
            }
        }

        // sort with translated names
        usort($availableAreas,function($a, $b) {
            if ($a["name"] == $b["name"]) {
                return 0;
            }
            return ($a["name"] < $b["name"]) ? -1 : 1;
        });

        $options["types"] = $availableAreas;

        if(is_array($options["group"])) {
            $groupingareas = array();
            foreach ($availableAreas as $area) {
                $groupingareas[$area["type"]] = $area["type"];
            }
            
            $groups = array();
            foreach ($options["group"] as $name => $areas) {

                $n = $name;
                if($this->view){
                    $n = $this->view->translateAdmin($name);
                }
                $groups[$n] = $areas;

                foreach($areas as $area) {
                    unset($groupingareas[$area]);
                }
            }

            if(count($groupingareas) > 0) {
                $uncatAreas = array();
                foreach ($groupingareas as $area) {
                    $uncatAreas[] = $area;
                }
                $n = "uncategorized";
                if($this->view){
                    $n= $this->view->translateAdmin($n);
                }
                $groups[$n] = $uncatAreas;
            }

            $options["group"] = $groups;
        }
        
        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }
        

        $this->options = $options;
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
        return $this->indices[$this->getCurrent()]["key"];
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
     * Receives a Webservice_Data_Document_Element from webservice import and fill the current tag's data
     *
     * @abstract
     * @param  Webservice_Data_Document_Element $data
     * @return void
     */
    public function getFromWebserviceImport($wsElement){
        throw new Exception("It's not possible to set areas via the webservice");
    }


    /**
     * @return array
     */
    public function getAreaDirs () {
        return Pimcore_ExtensionManager::getBrickDirectories();
    }

    public function getBrickConfigs() {
        return Pimcore_ExtensionManager::getBrickConfigs();
    }
}
