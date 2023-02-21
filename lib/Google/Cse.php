<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Google;

use Exception;
use Google\Service\CustomSearchAPI;
use Google\Service\CustomSearchAPI\Search;
use Pimcore;
use Pimcore\Cache;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Google\Cse\Item;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Model;
use Pimcore\Model\Paginator\PaginateListingInterface;

class Cse implements PaginateListingInterface
{
    /**
     * @param string $query
     * @param int $offset
     * @param int $perPage
     * @param array $config
     * @param string|null $facet
     *
     * @return self
     */
    public static function search(string $query, int $offset = 0, int $perPage = 10, array $config = [], string $facet = null): Cse
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

    /**
     * @return Item[]
     *
     * @throws Exception
     */
    public function load(): array
    {
        $client = Api::getSimpleClient();
        $config = $this->getConfig();
        $perPage = $this->getPerPage();
        $offset = $this->getOffset();
        $query = $this->getQuery();

        if ($client) {
            $search = new CustomSearchAPI($client);

            // determine language
            $language = Pimcore::getContainer()->get(LocaleServiceInterface::class)->findLocale();

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
                if (RuntimeCache::isRegistered($cacheKey)) {
                    $result = RuntimeCache::get($cacheKey);
                } else {
                    if (!$result = Cache::load($cacheKey)) {
                        $result = $search->cse->listCse($config);
                        Cache::save($result, $cacheKey, ['google_cse'], 3600, 999);
                        RuntimeCache::set($cacheKey, $result);
                    }
                }

                $this->readGoogleResponse($result);

                return $this->getResults(false);
            }

            return [];
        } else {
            throw new Exception('Google Simple API Key is not configured in System-Settings.');
        }
    }

    /**
     * @var Item[]
     */
    public array $results = [];

    public int $total = 0;

    public int $offset = 0;

    public int $perPage = 10;

    public array $config = [];

    public string $query = '';

    public array $raw = [];

    public array $facets = [];

    public function __construct(Search $googleResponse = null)
    {
        if ($googleResponse) {
            $this->readGoogleResponse($googleResponse);
        }
    }

    public function readGoogleResponse(Search $googleResponse): void
    {
        $items = [];

        $this->setRaw($googleResponse->getItems());

        // set search results
        $total = (int)$googleResponse->getSearchInformation()->getTotalResults();
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
                                    if ($id = (int) $matches[1]) {
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

                            $pimcoreResultItem->setImage($item['pagemap']['cse_image'][0]['src']);
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

    public function setOffset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setRaw(array $raw): static
    {
        $this->raw = $raw;

        return $this;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;

        return $this;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setQuery(string $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param Item[] $results
     *
     * @return $this
     */
    public function setResults(array $results): static
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @param bool $retry
     *
     * @return Item[]
     *
     * @throws Exception
     */
    public function getResults(bool $retry = true): array
    {
        if (empty($this->results) && $retry) {
            $this->load();
        }

        return $this->results;
    }

    public function setFacets(array $facets): static
    {
        $this->facets = $facets;

        return $this;
    }

    public function getFacets(): array
    {
        return $this->facets;
    }

    /**
     * Methods for PaginateListingInterface
     */
    public function count(): int
    {
        $this->getResults();

        return $this->getTotal();
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return Item[]
     *
     * @throws Exception
     */
    public function getItems(int $offset, int $itemCountPerPage): array
    {
        $this->setOffset($offset);
        $this->setPerPage($itemCountPerPage);

        return $this->load();
    }

    /**
     * Methods for Iterator
     */
    public function rewind(): void
    {
        reset($this->results);
    }

    public function current(): Item|bool
    {
        $this->getResults();

        return current($this->results);
    }

    public function key(): ?int
    {
        $this->getResults();

        return key($this->results);
    }

    public function next(): void
    {
        $this->getResults();
        next($this->results);
    }

    public function valid(): bool
    {
        $this->getResults();

        return $this->current() !== false;
    }
}
