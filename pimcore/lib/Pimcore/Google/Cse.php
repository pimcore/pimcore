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

class Pimcore_Google_Cse implements Zend_Paginator_Adapter_Interface, Zend_Paginator_AdapterAggregate, Iterator {

    /**
     * @param string $query
     * @param int $offset
     * @param int $perPage
     * @param array $config
     * @return Pimcore_Google_Cse
     */
    public function search ($query, $offset = 0, $perPage = 10, $config = array()) {
        $list = new self();
        $list->setConfig($config);
        $list->setOffset($offset);
        $list->setPerPage($perPage);
        $list->setQuery($query);

        return $list;
    }


    /**
     *
     */
    public function load() {
        $client = Pimcore_Google_Api::getSimpleClient();
        $config = $this->getConfig();
        $perPage = $this->getPerPage();
        $offset = $this->getOffset();
        $query = $this->getQuery();


        if($client) {
            $search = new apiCustomsearchService($client);

            // determine language
            $language = "";
            if(Zend_Registry::isRegistered("Zend_Locale")) {
                $locale = Zend_Registry::get("Zend_Locale");
                $language = $locale->getLanguage();
            }

            if(!array_key_exists("hl", $config) && !empty($language)) {
                $config["hl"] = $language;
            }

            if(!array_key_exists("lr", $config) && !empty($language)) {
                $config["lr"] = "lang_" . $language;
            }

            if($query) {
                if($offset) {
                    $config["start"] = $offset + 1;
                }
                if($perPage) {
                    $config["num"] = $perPage;
                }

                $cacheKey = "google_cse_" . md5($query . serialize($config));

                if(!$result = Pimcore_Model_Cache::load($cacheKey)) {
                    $result = $search->cse->listCse($query, $config);
                    Pimcore_Model_Cache::save($result, $cacheKey, array("google_cse"), 3600, 999);
                }

                $this->readGoogleResponse($result);

                return $this->getResults();
            }

            return array();
        } else {
            throw new Exception("Google Simple API Key is not configured in System-Settings.");
        }
    }

    /**
     * @var array
     */
    public $results = array();

    /**
     * @var int
     */
    public $total = 0;

    /**
     * @var int
     */
    public $offset = 0;

    /**
     * @var int
     */
    public $perPage = 10;

    /**
     * @var array
     */
    public $config = array();

    /**
     * @var string
     */
    public $query = "";

    /**
     * @var array
     */
    public $raw = array();

    public function __construct ($googleResponse = null) {
        if($googleResponse) {
                $this->readGoogleResponse($googleResponse);
        }
    }

    public function readGoogleResponse($googleResponse) {
        $this->setRaw($googleResponse);

        $this->setTotal(intval($googleResponse["searchInformation"]["totalResults"]));

        $items = array();
        if(array_key_exists("items", $googleResponse) && is_array($googleResponse["items"])) {
            foreach ($googleResponse["items"] as $item) {

                // check for relation to document or asset
                // first check for an image
                if(array_key_exists("pagemap", $item) && is_array($item["pagemap"])) {
                    if(array_key_exists("cse_image", $item["pagemap"]) && is_array($item["pagemap"]["cse_image"])) {
                        if($item["pagemap"]["cse_image"][0]) {
                            // try to get the asset id
                            if(preg_match("/thumb_([0-9]+)__/", $item["pagemap"]["cse_image"][0]["src"], $matches)) {
                                $test = $matches;
                                if($matches[1]) {
                                    if($image = Asset::getById($matches[1])) {
                                        if($image instanceof Asset_Image) {
                                            $item["image"] = $image;
                                        }
                                    }
                                }
                            }

                            if (!array_key_exists("image", $item)) {
                                $item["image"] = $item["pagemap"]["cse_image"][0]["src"];
                            }
                        }
                    }
                }

                // now a document
                $urlParts = parse_url($item["link"]);
                if($document = Document::getByPath($urlParts["path"])) {
                    $item["document"] = $document;
                }

                $items[] = new Pimcore_Google_Cse_Item($item);
            }
        }

        $this->setResults($items);
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param array $raw
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param int $total
     */
    public function setTotal($total)
    {
        $this->total = $total;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param int $perPage
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param array $results
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * @return array
     */
    public function getResults()
    {
        if(empty($this->results)) {
            $this->load();
        }
        return $this->results;
    }


    /**
     *
     * Methods for Zend_Paginator_Adapter_Interface
     */

    public function count() {
        return $this->getTotal();
    }

    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setPerPage($itemCountPerPage);
        return $this->load();
    }

    public function getPaginatorAdapter() {
        return $this;
    }


    /**
     * Methods for Iterator
     */

    public function rewind() {
        reset($this->results);
    }

    public function current() {
        $this->getResults();
        $var = current($this->results);
        return $var;
    }

    public function key() {
        $this->getResults();
        $var = key($this->results);
        return $var;
    }

    public function next() {
        $this->getResults();
        $var = next($this->results);
        return $var;
    }

    public function valid() {
        $this->getResults();
        $var = $this->current() !== false;
        return $var;
    }
}
