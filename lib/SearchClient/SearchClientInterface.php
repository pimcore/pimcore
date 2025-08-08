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

namespace Pimcore\SearchClient;

use Pimcore\SearchClient\Exception\ClientException;

interface SearchClientInterface
{
    /**
     * @throws ClientException
     */
    public function create(array $params): array;

    /**
     * @throws ClientException
     */
    public function search(array $params): array;

    /**
     * @throws ClientException
     */
    public function get(array $params): array;

    /**
     * @throws ClientException
     */
    public function exists(array $params): bool;

    /**
     * @throws ClientException
     */
    public function count(array $params): array;

    /**
     * @throws ClientException
     */
    public function index(array $params): array;

    /**
     * @throws ClientException
     */
    public function bulk(array $params): array;

    /**
     * @throws ClientException
     */
    public function delete(array $params): array;

    /**
     * @throws ClientException
     */
    public function updateByQuery(array $params): array;

    /**
     * @throws ClientException
     */
    public function deleteByQuery(array $params): array;

    /**
     * @throws ClientException
     */
    public function createIndex(array $params): array;

    /**
     * @throws ClientException
     */
    public function openIndex(array $params): array;

    /**
     * @throws ClientException
     */
    public function closeIndex(array $params): array;

    /**
     * @throws ClientException
     */
    public function getAllIndices(array $params): array;

    /**
     * @throws ClientException
     */
    public function existsIndex(array $params): bool;

    /**
     * @throws ClientException
     */
    public function reIndex(array $params): array;

    /**
     * @throws ClientException
     */
    public function refreshIndex(array $params = []): array;

    /**
     * @throws ClientException
     */
    public function flushIndex(array $params = []): array;

    /**
     * @throws ClientException
     */
    public function deleteIndex(array $params): array;

    /**
     * @throws ClientException
     */
    public function existsIndexAlias(array $params): bool;

    /**
     * @throws ClientException
     */
    public function getIndexAlias(array $params): array;

    /**
     * @throws ClientException
     */
    public function deleteIndexAlias(array $params): array;

    /**
     * @throws ClientException
     */
    public function getAllIndexAliases(array $params): array;

    /**
     * @throws ClientException
     */
    public function updateIndexAliases(array $params): array;

    /**
     * @throws ClientException
     */
    public function putIndexMapping(array $params): array;

    /**
     * @throws ClientException
     */
    public function getIndexMapping(array $params): array;

    /**
     * @throws ClientException
     */
    public function getIndexSettings(array $params): array;

    /**
     * @throws ClientException
     */
    public function putIndexSettings(array $params): array;

    /**
     * @throws ClientException
     */
    public function getIndexStats(array $params): array;
}
