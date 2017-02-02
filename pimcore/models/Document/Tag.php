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

namespace Pimcore\Model\Document;

use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Webservice;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
abstract class Tag extends Model\AbstractModel implements Model\Document\Tag\TagInterface
{

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
     * Contains the real name of the editable without the prefixes and suffixes
     * which are generated automatically by blocks and areablocks
     *
     * @var string
     */
    protected $realName;

    /**
     * Element belongs to the ID of the document
     *
     * @var integer
     */
    protected $documentId;

    /**
     * @var \Pimcore\Controller\Action
     */
    protected $controller;

    /**
     * @var \Pimcore\View
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
     * @param $type
     * @param $name
     * @param $documentId
     * @param null $config
     * @param null $controller
     * @param null $view
     * @param null $editmode
     * @return mixed
     */
    public static function factory($type, $name, $documentId, $config = null, $controller = null, $view = null, $editmode = null)
    {
        $tagClass = "\\Pimcore\\Model\\Document\\Tag\\" . ucfirst($type);

        // this is the fallback for custom document tags using prefixes
        // so we need to check if the class exists first
        if (!\Pimcore\Tool::classExists($tagClass)) {
            $oldStyleClass = "\\Document_Tag_" . ucfirst($type);
            if (\Pimcore\Tool::classExists($oldStyleClass)) {
                $tagClass = $oldStyleClass;
            }
        }

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
     * @return string
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

        $class = "pimcore_editable pimcore_tag_" . $this->getType();
        if (array_key_exists("class", $this->getOptions())) {
            $class .= (" " . $this->getOptions()["class"]);
        }

        if (isset($this->getOptions()["tag"]) && $this->getOptions()["tag"]) {
            $tag = $this->getOptions()["tag"];
        }
        else {
            $tag = "div";
        }

        return '
            <script type="text/javascript">
                editableConfigurations.push(' . $options . ');
            </script>
            <' . $tag . ' id="pimcore_editable_' . $this->getName() . '" class="' . $class . '"></' . $tag . '>
        ';
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getData();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setDocumentId($id)
    {
        $this->documentId = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return is_array($this->options) ? $this->options : [];
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @param \Pimcore\Controller\Action $controller
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return \Pimcore\Controller\Action
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param \Pimcore\View $view
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return \Pimcore\View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     */
    public function setRealName($realName)
    {
        $this->realName = $realName;
    }

    /**
     * Returns only the properties which should be serialized
     *
     * @return void
     */
    public function __sleep()
    {

        // here the "normal" task of __sleep ;-)
        $blockedVars = ["dao", "controller", "view", "editmode", "options"];
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
    public function __toString()
    {
        $return = "";

        try {
            if ($this->editmode) {
                $return = $this->admin();
            } else {
                $return = $this->frontend();
            }
        } catch (\Exception $e) {
            if (\Pimcore::inDebugMode()) {
                // the __toString method isn't allowed to throw exceptions
                $return = '<b style="color:#f00">' . $e->getMessage().'</b><br/>'.$e->getTraceAsString();
            }
            Logger::error("to string not possible - " . $e->getMessage());
        }

        if (is_string($return) || is_numeric($return)) {
            // we have to cast to string, because int/float is not auto-converted and throws an exception
            return (string) $return;
        }

        return '';
    }

    /**
     * @return boolean
     */
    public function getEditmode()
    {
        return $this->editmode;
    }

    /**
     * @param boolean $editmode
     * @return $this
     */
    public function setEditmode($editmode)
    {
        $this->editmode = (bool) $editmode;

        return $this;
    }

    /**
     * @return $this
     */
    public function getDataForResource()
    {
        $this->checkValidity();

        return $this->getData();
    }

    /**
     * @param $ownerDocument
     * @param array $tags
     * @return array
     */
    public function getCacheTags($ownerDocument, $tags = [])
    {
        return $tags;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     */
    public function resolveDependencies()
    {
        return [];
    }


    /**
     * Receives a standard class object from webservice import and fills the current tag's data
     *
     * @abstract
     * @param  Webservice\Data\Document\Element $wsElement
     * @param mixed $params
     * @return Webservice\Data\Document\Element
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        return $wsElement;
    }


    /**
     * Returns the current tag's data for web service export
     * @param mixed $params
     * @abstract
     * @return array
     */
    public function getForWebserviceExport($document = null, $params = [])
    {
        $keys = get_object_vars($this);

        $el = [];
        foreach ($keys as $key => $value) {
            if ($value instanceof Model\Element\ElementInterface) {
                $value = $value->getId();
            }
            $className = Webservice\Data\Mapper::findWebserviceClass($value, "out");
            $el[$key] = Webservice\Data\Mapper::map($value, $className, "out");
        }

        unset($el["dao"]);
        unset($el["documentId"]);
        unset($el["controller"]);
        unset($el["view"]);
        unset($el["editmode"]);

        $el = Webservice\Data\Mapper::toObject($el);

        return $el;
    }



    /**
     * @return bool
     */
    public function checkValidity()
    {
        return true;
    }

    /**
     * @param $inherited
     * @return $this
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
     * @param $type
     * @param $name
     * @param null $document
     * @return string
     * @throws \Exception
     * @throws \Zend_Exception
     */
    public static function buildTagName($type, $name, $document = null)
    {
        if (!preg_match("@^[a-zA-Z0-9\-_]+$@", $name)) {
            throw new \Exception("Only valid CSS class selectors are allowed as the name for an editable (which is basically [a-zA-Z0-9\-_]+), your name was: " . $name);
        }

        // check for persona content
        if ($document && $document instanceof Document\Page && $document->getUsePersona()) {
            $name = $document->getPersonaElementName($name);
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the tag is inside a block
        if (\Zend_Registry::isRegistered("pimcore_tag_block_current")) {
            $blocks = \Zend_Registry::get("pimcore_tag_block_current");

            $numeration = \Zend_Registry::get("pimcore_tag_block_numeration");
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

        if (strlen($name) > 750) {
            throw new \Exception("Composite name is longer than 750 characters - use shorter names for your editables or reduce amount of nesting levels. Name is: " . $name);
        }

        return $name;
    }
}
