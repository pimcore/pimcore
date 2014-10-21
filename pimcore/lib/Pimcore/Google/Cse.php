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

namespace Pimcore\Google;

use Pimcore\Google\Api;
use Pimcore\Model\Cache;
use Pimcore\Google\Cse\Item;
use Pimcore\Model;

class Cse implements \Zend_Paginator_Adapter_Interface, \Zend_Paginator_AdapterAggregate, \Iterator {

    /**
     * @param $query
     * @param int $offset
     * @param int $perPage
     * @param array $config
     * @param null $facet
     * @return Cse
     */
    public static function search ($query, $offset = 0, $perPage = 10, array $config = array(), $facet = null) {
        $list = new self();
        $list->setConfig($config);
        $list->setOffset($offset);
        $list->setPerPage($perPage);
        $list->setQuery($query);

        if(!empty($facet)) {
            $list->setQuery($list->getQuery() . " more:" . $facet);
        }

        return $list;
    }

    /**
     *
     */
    public function load() {
        $client = Api::getSimpleClient();
        $config = $this->getConfig();
        $perPage = $this->getPerPage();
        $offset = $this->getOffset();
        $query = $this->getQuery();


        if($client) {
            $search = new \Google_Service_Customsearch($client);

            // determine language
            $language = "";
            if(\Zend_Registry::isRegistered("Zend_Locale")) {
                $locale = \Zend_Registry::get("Zend_Locale");
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
                if(empty($perPage)) {
                    $perPage = 10;
                }

                $config["num"] = $perPage;

                $cacheKey = "google_cse_" . md5($query . serialize($config));

                // this is just a protection so that no query get's sent twice in a request (loops, ...)
                if(\Zend_Registry::isRegistered($cacheKey)) {
                    $result = \Zend_Registry::get($cacheKey);
                } else {
                    if(!$result = Cache::load($cacheKey)) {
                        $result = $search->cse->listCse($query, $config);
                        Cache::save($result, $cacheKey, array("google_cse"), 3600, 999);
                        \Zend_Registry::set($cacheKey, $result);
                    }
                }

                $this->readGoogleResponse($result);

                return $this->getResults(false);
            }

            return array();
        } else {
            throw new \Exception("Google Simple API Key is not configured in System-Settings.");
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

    /**
     * @var array
     */
    public $facets = array();


    /**
     * @param null|mixed $googleResponse
     */
    public function __construct ($googleResponse = null) {
        if($googleResponse) {
            $this->readGoogleResponse($googleResponse);
        }
    }

    /**
     * @param $googleResponse
     */
    public function readGoogleResponse($googleResponse) {

        $googleResponse = $googleResponse["modelData"];
        $this->setRaw($googleResponse);

        // available factes
        if(array_key_exists("context", $googleResponse) && is_array($googleResponse["context"])) {
            if(array_key_exists("facets", $googleResponse["context"]) && is_array($googleResponse["context"]["facets"])) {
                $facets = array();
                foreach ($googleResponse["context"]["facets"] as $facet) {
                    $facets[$facet[0]["label"]] = $facet[0]["anchor"];
                }
                $this->setFacets($facets);
            }
        }

        // results incl. promotions, search results, ...
        $items = array();

        // set promotions
        if(array_key_exists("promotions", $googleResponse) && is_array($googleResponse["promotions"])) {
            foreach ($googleResponse["promotions"] as $promo) {
                $promo["type"] = "promotion";
                $promo["formattedUrl"] = preg_replace("@^https?://@", "", $promo["link"]);
                $promo["htmlFormattedUrl"] = $promo["formattedUrl"];

                $items[] = new Item($promo);
            }
        }


        // set search results
        $total = intval($googleResponse["searchInformation"]["totalResults"]);
        if($total > 100) {
            $total = 100;
        }
        $this->setTotal($total);


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
                                    if($image = Model\Asset::getById($matches[1])) {
                                        if($image instanceof Model\Asset\Image) {
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
                if($document = Model\Document::getByPath($urlParts["path"])) {
                    $item["document"] = $document;
                }

                $item["type"] = "searchresult";

                $items[] = new Item($item);
            }
        }

        $this->setResults($items);
    }

    /**
     * @param $offset
     * @return $this
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param $raw
     * @return $this
     */
    public function setRaw($raw)
    {
        $this->raw = $raw;
        return $this;
    }

    /**
     * @return array
     */
    public function getRaw()
    {
        return $this->raw;
    }

    /**
     * @param $total
     * @return $this
     */
    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        return $this->total;
    }

    /**
     * @param $perPage
     * @return $this
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param $config
     * @return $this
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param $results
     * @return $this
     */
    public function setResults($results)
    {
        $this->results = $results;
        return $this;
    }

    /**
     * @return array
     */
    public function getResults($retry=true)
    {
        if(empty($this->results) && $retry) {
            $this->load();
        }
        return $this->results;
    }

    /**
     * @param $facets
     * @return $this
     */
    public function setFacets($facets)
    {
        $this->facets = $facets;
        return $this;
    }

    /**
     * @return array
     */
    public function getFacets()
    {
        return $this->facets;
    }

    /**
     *
     * Methods for \Zend_Paginator_Adapter_Interface
     */

    /**
     * @return int
     */
    public function count() {
        $this->getResults();
        return $this->getTotal();
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     * @return array
     */
    public function getItems($offset, $itemCountPerPage) {
        $this->setOffset($offset);
        $this->setPerPage($itemCountPerPage);

        $items = $this->load();

        return $items;
    }

    /**
     * @return $this|\Zend_Paginator_Adapter_Interface
     */
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
