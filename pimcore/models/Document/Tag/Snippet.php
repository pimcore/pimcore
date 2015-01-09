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
use Pimcore\Model\Cache;
use Pimcore\Model\Document;

class Snippet extends Model\Document\Tag {

    /**
     * Contains the ID of the linked snippet
     *
     * @var integer
     */
    public $id;

    /**
     * Contains the object for the snippet
     *
     * @var Document\Snippet
     */
    public $snippet;


    /**
     * @see Document\Tag\TagInterface::getType
     * @return string
     */
    public function getType() {
        return "snippet";
    }

    /**
     * @see Document\Tag\TagInterface::getData
     * @return mixed
     */
    public function getData() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int) $this->id;
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode() {
        if ($this->snippet instanceof Document\Snippet) {
            return array(
                "id" => $this->id,
                "path" => $this->snippet->getFullPath()
            );
        }
        return null;
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     * @return string
     */
    public function frontend() {
        if ($this->getView() instanceof \Zend_View) {
            try {
                if ($this->snippet instanceof Document\Snippet) {
                    $params = $this->options;
                    $params["document"] = $this->snippet;

                    if ($this->snippet->isPublished()) {

                        // check if output-cache is enabled, if so, we're also using the cache here
                        $cacheKey = null;
                        if($cacheConfig = \Pimcore\Tool\Frontend::isOutputCacheEnabled()) {

                            // cleanup params to avoid serializing Element\ElementInterface objects
                            $cacheParams = $params;
                            array_walk($cacheParams, function (&$value, $key) {
                                if($value instanceof Model\Element\ElementInterface) {
                                    $value = $value->getId();
                                }
                            });

                            $cacheKey = "tag_snippet__" . md5(serialize($cacheParams));
                            if($content = Cache::load($cacheKey)) {
                                return $content;
                            }
                        }

                        $content = $this->getView()->action($this->snippet->getAction(), $this->snippet->getController(), $this->snippet->getModule(), $params);

                        // write contents to the cache, if output-cache is enabled
                        if($cacheConfig) {
                            Cache::save($content, $cacheKey, array("output"), $cacheConfig["lifetime"]);
                        }

                        // we need to add a component id to all first level html containers
                        $componentId = 'document:' . $this->getDocumentId() . '.type:tag-snippet.name:' . $this->snippet->getId();
                        $content = \Pimcore\Tool\Frontend::addComponentIdToHtml($content, $componentId);

                        return $content;
                    }
                    return "";
                }
            } catch (\Exception $e) {
                if(\Pimcore::inDebugMode()) {
                    return "ERROR: " . $e->getMessage() . " (for details see debug.log)";
                }
                \Logger::error($e);
            }
        } else {
            return null;
        }
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     * @param mixed $data
     * @return void
     */
    public function setDataFromResource($data) {
        if (intval($data) > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }
        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     * @param mixed $data
     * @return void
     */
    public function setDataFromEditmode($data) {
        if (intval($data) > 0) {
            $this->id = $data;
            $this->snippet = Document\Snippet::getById($this->id);
        }
        return $this;
    }
    
    /**
     * @return boolean
     */
    public function isEmpty() {
        if($this->snippet instanceof Document\Snippet) {
            return false;
        }
        return true;
    }


    /**
     * @return array
     */
    public function resolveDependencies () {
        $dependencies = array();
        
        if ($this->snippet instanceof Document\Snippet) {

            $key = "document_" . $this->snippet->getId();

            $dependencies[$key] = array(
                "id" => $this->snippet->getId(),
                "type" => "document"
            );
        }
        
        return $dependencies;
    }


    /**
     * @param Document\Webservice\Data\Document\Element $wsElement
     * @param null $idMapper
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $idMapper = null) {
        $data = $wsElement->value;
        if ($data->id !==null) {

            $this->id = $data->id;
            if (is_numeric($this->id)) {
                $this->snippet = Document\Snippet::getById($this->id);
                if (!$this->snippet instanceof Document\Snippet) {
                    throw new \Exception("cannot get values from web service import - referenced snippet with id [ " . $this->id . " ] is unknown");
                }
            } else {
                throw new \Exception("cannot get values from web service import - id is not valid");
            }


        }
    }


    /**
     * @return array
     */
    public function __sleep() {

        $finalVars = array();
        $parentVars = parent::__sleep();
        $blockedVars = array("snippet");
        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * this method is called by Document\Service::loadAllDocumentFields() to load all lazy loading fields
     *
     * @return void
     */
    public function load () {
        $this->snippet = Document::getById($this->id);
    }



    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     * @param array $idMapping
     * @return void
     */
    public function rewriteIds($idMapping) {
        $id = $this->getId();
        if(array_key_exists("document",$idMapping) && array_key_exists($id, $idMapping["document"])) {
            $this->id = $idMapping["document"][$id];
        }
    }

    /**
     * @param Document\Snippet $snippet
     */
    public function setSnippet($snippet)
    {
        $this->snippet = $snippet;
    }

    /**
     * @return Document\Snippet
     */
    public function getSnippet()
    {
        return $this->snippet;
    }
}
