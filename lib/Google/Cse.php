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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Google;

use Pimcore\Cache;
use Pimcore\Google\Cse\Item;
use Pimcore\Model;
use Zend\Paginator\Adapter\AdapterInterface;
use Zend\Paginator\AdapterAggregateInterface;

class Cse implements \Iterator, AdapterInterface, AdapterAggregateInterface
{
    /**
     * @param string $query
     * @param int $offset
     * @param int $perPage
     * @param array $config
     * @param string|null $facet
     *
     * @return Cse
     */
    public static function search($query, $offset = 0, $perPage = 10, array $config = [], $facet = null)
    {
        $list = new self();
        $list->setConfig($config);
        $list->setOffset($offset);
        $list->setPerPage($perPage);
        $list->setQuery($query);

        if (!empty($facet)) {
            $list->setQuery($list->getQuery() . ' more:' . $facet);
        }

        return $list;
    }

    public function load()
    {
        $client = Api::getSimpleClient();
        $config = $this->getConfig();
        $perPage = $this->getPerPage();
        $offset = $this->getOffset();
        $query = $this->getQuery();

        if ($client) {
            $search = new \Google_Service_Customsearch($client);

            // determine language
            $language = \Pimcore::getContainer()->get('pimcore.locale')->findLocale();

            if ($position = strpos($language, '_')) {
                $language = substr($language, 0, $position);
            }

            if (!array_key_exists('hl', $config) && !empty($language)) {
                $config['hl'] = $language;
            }

            if (!array_key_exists('lr', $config) && !empty($language)) {
                $config['lr'] = 'lang_' . $language;
            }

            if ($query) {
                if ($offset) {
                    $config['start'] = $offset + 1;
                }
                if (empty($perPage)) {
                    $perPage = 10;
                }

                $config['num'] = $perPage;
                $config['q'] = $query;

                $cacheKey = 'google_cse_' . md5($query . serialize($config));

                // this is just a protection so that no query get's sent twice in a request (loops, ...)
                if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
                    $result = \Pimcore\Cache\Runtime::get($cacheKey);
                } else {
                    if (!$result = Cache::load($cacheKey)) {
                        $result = $search->cse->listCse($config);
                        Cache::save($result, $cacheKey, ['google_cse'], 3600, 999);
                        \Pimcore\Cache\Runtime::set($cacheKey, $result);
                    }
                }

                $this->readGoogleResponse($result);

                return $this->getResults(false);
            }

            return [];
        } else {
            throw new \Exception('Google Simple API Key is not configured in System-Settings.');
        }
    }

    /**
     * @var Item[]
     */
    public $results = [];

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
    public $config = [];

    /**
     * @var string
     */
    public $query = '';

    /**
     * @var array
     */
    public $raw = [];

    /**
     * @var array
     */
    public $facets = [];

    /**
     * @param null|mixed $googleResponse
     */
    public function __construct($googleResponse = null)
    {
        if ($googleResponse) {
            $this->readGoogleResponse($googleResponse);
        }
    }

    /**
     * @param \Google_Service_Customsearch_Search $googleResponse
     */
    public function readGoogleResponse(\Google_Service_Customsearch_Search $googleResponse)
    {
        $items = [];

        $this->setRaw($googleResponse);

        // set search results
        $total = intval($googleResponse->getSearchInformation()->getTotalResults());
        if ($total > 100) {
            $total = 100;
        }
        $this->setTotal($total);

        $results = $googleResponse->getItems();
        if (is_array($results)) {
            foreach ($results as $item) {
                $pimcoreResultItem = new Item($item);

                // check for relation to document or asset
                // first check for an image
                $pagemap = $item->getPagemap();
                if (is_array($pagemap)) {
                    if (array_key_exists('cse_image', $item['pagemap']) && is_array($item['pagemap']['cse_image'])) {
                        if ($item['pagemap']['cse_image'][0]) {
                            // try to get the asset id
                            $id = false;
                            $regexes = [
                                '/image-thumb__([0-9]+)__/',
                                '/([0-9]+)\/thumb__/',
                                '/thumb_([0-9]+)__/',
                            ];

                            foreach ($regexes as $regex) {
                                if (preg_match($regex, $item['pagemap']['cse_image'][0]['src'], $matches)) {
                                    if ($id = $matches[1]) {
                                        break;
                                    }
                                }
                            }

                            if ($id) {
                                if ($image = Model\Asset::getById($id)) {
                                    if ($image instanceof Model\Asset\Image) {
                                        $pimcoreResultItem->setImage($image);
                                    }
                                }
                            }

                            if (!array_key_exists('image', $item)) {
                                $pimcoreResultItem->setImage($item['pagemap']['cse_image'][0]['src']);
                            }
                        }
                    }
                }

                // now a document
                $urlParts = parse_url($item->getLink());
                if ($document = Model\Document::getByPath($urlParts['path'])) {
                    $pimcoreResultItem->setDocument($document);
                }

                $pimcoreResultItem->setType('searchresult');
                $items[] = $pimcoreResultItem;
            }
        }

        $this->setResults($items);
    }

    /**
     * @param int $offset
     *
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
     * @param array $raw
     *
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
     * @param int $total
     *
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
     * @param int $perPage
     *
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
     * @param array $config
     *
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
     * @param string $query
     *
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
     * @param Item[] $results
     *
     * @return $this
     */
    public function setResults($results)
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @param bool $retry
     *
     * @return Item[]
     */
    public function getResults($retry = true)
    {
        if (empty($this->results) && $retry) {
            $this->load();
        }

        return $this->results;
    }

    /**
     * @param array $facets
     *
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
     * Methods for AdapterInterface
     */

    /**
     * @return int
     */
    public function count()
    {
        $this->getResults();

        return $this->getTotal();
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return array
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setPerPage($itemCountPerPage);

        $items = $this->load();

        return $items;
    }

    /**
     * @return self
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }

    /**
     * Methods for Iterator
     */
    public function rewind()
    {
        reset($this->results);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->getResults();
        $var = current($this->results);

        return $var;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        $this->getResults();
        $var = key($this->results);

        return $var;
    }

    /**
     * @return mixed
     */
    public function next()
    {
        $this->getResults();
        $var = next($this->results);

        return $var;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        $this->getResults();
        $var = $this->current() !== false;

        return $var;
    }
}
