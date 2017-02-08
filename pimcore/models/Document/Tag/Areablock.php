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

use Pimcore\ExtensionManager;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Pimcore\Translate;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Areablock extends Model\Document\Tag
{
    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = [];

    /**
     * Current step of the block while iteration
     *
     * @var integer
     */
    public $current = 0;

    /**
     * @var array
     */
    public $currentIndex;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType()
    {
        return "areablock";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData()
    {
        return $this->indices;
    }

    /**
     * @see Document\Tag\TagInterface::admin
     */
    public function admin()
    {
        $this->frontend();
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     */
    public function frontend()
    {
        if (!is_array($this->indices)) {
            $this->indices = [];
        }
        reset($this->indices); while ($this->loop());
    }

    /**
     * @param $index
     */
    public function renderIndex($index)
    {
        $this->start();

        $this->currentIndex = $this->indices[$index];
        $this->current = $index;

        $this->blockConstruct();
        $this->blockStart();

        $this->content();

        $this->blockDestruct();
        $this->blockEnd();
        $this->end();
    }

    /**
     *
     */
    public function loop()
    {
        $disabled = false;
        $options = $this->getOptions();
        $manual = false;
        if (is_array($options) && array_key_exists("manual", $options) && $options["manual"] == true) {
            $manual = true;
        }

        if ($this->current > 0) {
            if (!$manual && $this->blockStarted) {
                $this->blockDestruct();
                $this->blockEnd();

                $this->blockStarted = false;
            }
        } else {
            if (!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->options["limit"]) {
            $index = current($this->indices);
            next($this->indices);

            $this->currentIndex = $index;
            if (!empty($options["allowed"]) && !in_array($index["type"], $options["allowed"])) {
                $disabled = true;
            }

            if (!$this->isBrickEnabled($index["type"]) && $options['dontCheckEnabled'] != true) {
                $disabled = true;
            }

            $this->blockStarted = false;

            if (!$manual && !$disabled) {
                $this->blockConstruct();
                $this->blockStart();

                $this->blockStarted = true;
                $this->content();
            } elseif (!$manual) {
                $this->current++;
            }

            return true;
        } else {
            if (!$manual) {
                $this->end();
            }

            return false;
        }
    }

    /**
     *
     */
    public function content()
    {
        // create info object and assign it to the view
        $info = new Area\Info();
        try {
            $info->setId($this->currentIndex["type"]);
            $info->setTag($this);
            $info->setIndex($this->current);
        } catch (\Exception $e) {
            Logger::err($e);
        }

        $params = [];
        if (isset($options["params"]) && is_array($options["params"]) && array_key_exists($this->currentIndex["type"], $options["params"])) {
            if (is_array($options["params"][$this->currentIndex["type"]])) {
                $params = $options["params"][$this->currentIndex["type"]];
            }
        }

        // TODO inject area handler via DI when tags are built through container
        $tagHandler = \Pimcore::getContainer()->get('pimcore.document.tag.handler');
        $tagHandler->renderAreaFrontend($info, $params);

        $this->current++;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->indices = Tool\Serialize::unserialize($data);
        if (!is_array($this->indices)) {
            $this->indices = [];
        }

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->indices = $data;

        return $this;
    }

    /**
     *
     */
    public function blockConstruct()
    {
        // set the current block suffix for the child elements (0, 1, 3, ...) | this will be removed in Pimcore_View_Helper_Tag::tag
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        $suffixes[] = $this->indices[$this->current]["key"];
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    /**
     *
     */
    public function blockDestruct()
    {
        $suffixes = \Zend_Registry::get("pimcore_tag_block_numeration");
        array_pop($suffixes);
        \Zend_Registry::set("pimcore_tag_block_numeration", $suffixes);
    }

    protected function getToolBarDefaultConfig()
    {
        $buttonWidth = 168;

        // @extjs6
        if (!\Pimcore\Tool\Admin::isExtJS6()) {
            $buttonWidth = 154;
        }

        return [
            "areablock_toolbar" => [
                "title" => "",
                "width" => 172,
                "x" => 20,
                "y" => 50,
                "xAlign" => "left",
                "buttonWidth" => $buttonWidth,
                "buttonMaxCharacters" => 20
            ]
        ];
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return void
     */
    public function start()
    {
        reset($this->indices);
        $this->setupStaticEnvironment();

        // get configuration data for admin
        if (method_exists($this, "getDataEditmode")) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        $configOptions = array_merge($this->getToolBarDefaultConfig(), $this->getOptions());

        $options = [
            "options" => $configOptions,
            "data" => $data,
            "name" => $this->getName(),
            "id" => "pimcore_editable_" . $this->getName(),
            "type" => $this->getType(),
            "inherited" => $this->getInherited()
        ];
        $options = @\Zend_Json::encode($options);
        //$options = base64_encode($options);

        $this->outputEditmode('
            <script type="text/javascript">
                editableConfigurations.push('.$options.');
            </script>
        ');

        // set name suffix for the whole block element, this will be addet to all child elements of the block
        $suffixes = [];
        if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
        }
        $suffixes[] = $this->getName();
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $class = "pimcore_editable pimcore_tag_" . $this->getType();
        if (array_key_exists("class", $this->getOptions())) {
            $class .= (" " . $this->getOptions()["class"]);
        }

        $this->outputEditmode('<div id="pimcore_editable_' . $this->getName() . '" name="' . $this->getName() . '" class="' . $class . '" type="' . $this->getType() . '">');

        return $this;
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     *
     * @return void
     */
    public function end()
    {
        $this->current = 0;

        // remove the suffix which was set by self::start()
        $suffixes = [];
        if (\Zend_Registry::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Zend_Registry::get("pimcore_tag_block_current");
            array_pop($suffixes);
        }
        \Zend_Registry::set("pimcore_tag_block_current", $suffixes);

        $this->outputEditmode("</div>");
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @return void
     */
    public function blockStart()
    {
        $this->outputEditmode('<div class="pimcore_area_entry pimcore_block_entry ' . $this->getName() . '" key="' . $this->indices[$this->current]["key"] . '" type="' . $this->indices[$this->current]["type"] . '">');
        $this->outputEditmode('<div class="pimcore_block_buttons_' . $this->getName() . ' pimcore_block_buttons">');
        $this->outputEditmode('<div class="pimcore_block_plus_' . $this->getName() . ' pimcore_block_plus"></div>');
        $this->outputEditmode('<div class="pimcore_block_minus_' . $this->getName() . ' pimcore_block_minus"></div>');
        $this->outputEditmode('<div class="pimcore_block_up_' . $this->getName() . ' pimcore_block_up"></div>');
        $this->outputEditmode('<div class="pimcore_block_down_' . $this->getName() . ' pimcore_block_down"></div>');
        $this->outputEditmode('<div class="pimcore_block_type_' . $this->getName() . ' pimcore_block_type"></div>');
        $this->outputEditmode('<div class="pimcore_block_options_' . $this->getName() . ' pimcore_block_options"></div>');
        $this->outputEditmode('<div class="pimcore_block_clear_' . $this->getName() . ' pimcore_block_clear"></div>');
        $this->outputEditmode('</div>');
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     *
     * @return void
     */
    public function blockEnd()
    {
        $this->outputEditmode('</div>');
    }

    /**
     * Sends data to the output stream
     *
     * @param string $v
     * @return void
     */
    public function outputEditmode($v)
    {
        if ($this->getEditmode()) {
            echo $v . "\n";
        }
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
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        // we need to set this here otherwise custom areaDir's won't work
        $this->options = $options;

        if (!isset($options["allowed"]) || !is_array($options["allowed"])) {
            $options["allowed"] = [];
        }

        // TODO inject area handler via DI when tags are built through container
        $tagHandler = \Pimcore::getContainer()->get('pimcore.document.tag.handler');

        $availableAreas = $tagHandler->getAvailableAreablockAreas($this, $options);
        $availableAreas = $this->sortAvailableAreas($availableAreas, $options);

        $options["types"] = $availableAreas;

        if (isset($options["group"]) && is_array($options["group"])) {
            $groupingareas = [];
            foreach ($availableAreas as $area) {
                $groupingareas[$area["type"]] = $area["type"];
            }

            $groups = [];
            foreach ($options["group"] as $name => $areas) {
                $n = $name;
                if ($this->editmode) {
                    $n = Translate::transAdmin($name);
                }
                $groups[$n] = $areas;

                foreach ($areas as $area) {
                    unset($groupingareas[$area]);
                }
            }

            if (count($groupingareas) > 0) {
                $uncatAreas = [];
                foreach ($groupingareas as $area) {
                    $uncatAreas[] = $area;
                }
                $n = "Uncategorized";
                if ($this->editmode) {
                    $n = Translate::transAdmin($n);
                }
                $groups[$n] = $uncatAreas;
            }

            $options["group"] = $groups;
        }

        if (empty($options["limit"])) {
            $options["limit"] = 1000000;
        }


        $this->options = $options;

        return $this;
    }

    /**
     * Sorts areas by index (sorting option) first, then by name
     *
     * @param array $areas
     * @param array $options
     *
     * @return array
     */
    protected function sortAvailableAreas(array $areas, array $options)
    {
        if (isset($options["sorting"]) && is_array($options["sorting"]) && count($options["sorting"])) {
            $sorting = $options["sorting"];
        } else {
            if (isset($options["allowed"]) && is_array($options["allowed"]) && count($options["allowed"])) {
                $sorting = $options["allowed"];
            } else {
                $sorting = [];
            }
        }

        $result = [
            'name'  => [],
            'index' => []
        ];

        foreach ($areas as $area) {
            $sortIndex = false;
            $sortKey   = "name"; //allowed and sorting is not set || areaName is not in allowed

            if (!empty($sorting)) {
                $sortIndex = array_search($area['type'], $sorting);
                $sortKey   = $sortIndex === false ? $sortKey : "index";
            }

            if ($sortIndex) {
                $area['sortIndex'] = $sortIndex;
            }

            $result[$sortKey][] = $area;
        }

        // sort with translated names
        if (count($result["name"])) {
            usort($result["name"], function ($a, $b) {
                if ($a["name"] == $b["name"]) {
                    return 0;
                }

                return ($a["name"] < $b["name"]) ? -1 : 1;
            });
        }

        // sort by allowed brick config order
        if (count($result["index"])) {
            usort($result["index"], function ($a, $b) {
                return $a["sortIndex"] - $b["sortIndex"];
            });
        }

        $result = array_merge($result["index"], $result["name"]);

        return $result;
    }

    /**
     * Return the amount of block elements
     *
     * @return integer
     */
    public function getCount()
    {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return integer
     */
    public function getCurrent()
    {
        return $this->current-1;
    }

    /**
     * Return current index
     *
     * @return integer
     */
    public function getCurrentIndex()
    {
        return $this->indices[$this->getCurrent()]["key"];
    }

    /**
     * If object was serialized, set the counter back to 0
     *
     * @return void
     */
    public function __wakeup()
    {
        $this->current = 0;
        reset($this->indices);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool) count($this->indices);
    }


    /**
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param mixed $params
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $wsElement->value;
        if (($data->indices === null or is_array($data->indices)) and ($data->current==null or is_numeric($data->current))
            and ($data->currentIndex==null or is_numeric($data->currentIndex))) {
            $indices = $data->indices;
            if ($indices instanceof \stdclass) {
                $indices = (array) $indices;
            }

            $this->indices = $indices;
            $this->current = $data->current;
            $this->currentIndex = $data->currentIndex;
        } else {
            throw new \Exception("cannot get  values from web service import - invalid data");
        }
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return bool
     */
    public function isCustomAreaPath()
    {
        $options = $this->getOptions();

        return array_key_exists("areaDir", $options);
    }

    /**
     * @param $name
     * @return bool
     */
    public function isBrickEnabled($name)
    {
        // TODO remove custom area path logic
        if ($this->isCustomAreaPath()) {
            return true;
        }

        // TODO decide what to do with extensions.php
        return ExtensionManager::isEnabled("brick", $name);
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return string
     */
    public function getAreaDirectory()
    {
        $options = $this->getOptions();

        return PIMCORE_DOCUMENT_ROOT . "/" . trim($options["areaDir"], "/");
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @param $name
     * @return string
     */
    public function getPathForBrick($name)
    {
        if ($this->isCustomAreaPath()) {
            return $this->getAreaDirectory() . "/" . $name;
        }

        return ExtensionManager::getPathForExtension($name, "brick");
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @param $name
     * @throws \Exception
     */
    public function getBrickConfig($name)
    {
        if ($this->isCustomAreaPath()) {
            $path = $this->getAreaDirectory();

            return ExtensionManager::getBrickConfig($name, $path);
        }

        return ExtensionManager::getBrickConfig($name);
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return array
     */
    public function getAreaDirs()
    {
        if ($this->isCustomAreaPath()) {
            return ExtensionManager::getBrickDirectories($this->getAreaDirectory());
        }

        return ExtensionManager::getBrickDirectories();
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return array|mixed
     */
    public function getBrickConfigs()
    {
        if ($this->isCustomAreaPath()) {
            return ExtensionManager::getBrickConfigs($this->getAreaDirectory());
        }

        return ExtensionManager::getBrickConfigs();
    }

    /**
     * @param $name
     *
     * @return Areablock\Item[]
     */
    public function getElement($name)
    {
        // init
        $doc = Model\Document\Page::getById($this->getDocumentId());

        $list = [];
        foreach ($this->getData() as $index => $item) {
            if ($item['type'] == $name) {
                $list[ $index ] = new Areablock\Item($doc, $this->getName(), $item['key']);
            }
        }

        return $list;
    }
}
