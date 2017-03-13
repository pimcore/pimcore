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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\ExtensionManager;
use Pimcore\Model;

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
        $options = $options = json_encode($options);

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
        $suffixes = [];
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
        }
        $suffixes[] = $this->getName();
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $suffixes);

        $this->current = $count;

        // create info object and assign it to the view
        $info = null;
        try {
            $info = new Area\Info();
            $info->setId($options['type']);
            $info->setTag($this);
            $info->setIndex($count);
        } catch (\Exception $e) {
            $info = null;
        }

        $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
        $suffixes[] = 1;
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $suffixes);

        $params = [];
        if (is_array($options["params"]) && array_key_exists($options["type"], $options["params"])) {
            if (is_array($options["params"][$options["type"]])) {
                $params = $options["params"][$options["type"]];
            }
        }

        // TODO inject area handler via DI when tags are built through container
        $tagHandler = \Pimcore::getContainer()->get('pimcore.document.tag.handler');
        $tagHandler->renderAreaFrontend($info, $params);

        $suffixes = [];
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_numeration')) {
            $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
            array_pop($suffixes);
        }
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $suffixes);

        $suffixes = [];
        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_tag_block_current')) {
            $suffixes = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
            array_pop($suffixes);
        }
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $suffixes);
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
     */
    public function setupStaticEnvironment()
    {

        // setup static environment for blocks
        if (\Pimcore\Cache\Runtime::isRegistered("pimcore_tag_block_current")) {
            $current = \Pimcore\Cache\Runtime::get("pimcore_tag_block_current");
            if (!is_array($current)) {
                $current = [];
            }
        } else {
            $current = [];
        }

        if (\Pimcore\Cache\Runtime::isRegistered("pimcore_tag_block_numeration")) {
            $numeration = \Pimcore\Cache\Runtime::get("pimcore_tag_block_numeration");
            if (!is_array($numeration)) {
                $numeration = [];
            }
        } else {
            $numeration = [];
        }

        \Pimcore\Cache\Runtime::set("pimcore_tag_block_numeration", $numeration);
        \Pimcore\Cache\Runtime::set("pimcore_tag_block_current", $current);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return false;
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return array
     */
    public function getAreaDirs()
    {
        return ExtensionManager::getBrickDirectories();
    }

    /**
     * @deprecated Only used in legacy mode
     *
     * @return array|mixed
     */
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
