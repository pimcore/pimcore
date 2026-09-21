<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\InstallBundle\EnvVarDefinition\Definitions;

/**
 * Elasticsearch search engine definition.
 *
 * Collects a single `PIMCORE_ELASTICSEARCH_DSN` env var with the format:
 * elasticsearch://user:pass@host:port?ssl=bool&ssl_verify=bool
 */
final readonly class ElasticsearchEnvVarDefinition extends AbstractSearchEngineEnvVarDefinition
{
    public function getKey(): string
    {
        return 'elasticsearch';
    }

    public function getLabel(): string
    {
        return 'Elasticsearch';
    }

    public function getSectionName(): string
    {
        return 'pimcore/elasticsearch-client';
    }

    protected function getScheme(): string
    {
        return 'elasticsearch';
    }

    protected function getEnvVarName(): string
    {
        return 'PIMCORE_ELASTICSEARCH_DSN';
    }

    protected function getDefaultDsn(): string
    {
        return 'elasticsearch://elastic@localhost:9200?ssl=true&ssl_verify=true';
    }

    protected function getDefaultPort(): int
    {
        return 9200;
    }

    protected function getDefaultSsl(): bool
    {
        return true;
    }

    protected function getDefaultSslVerify(): bool
    {
        return true;
    }
}
