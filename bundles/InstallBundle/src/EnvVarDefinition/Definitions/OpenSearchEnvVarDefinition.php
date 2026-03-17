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
 * OpenSearch search engine definition.
 *
 * Collects a single `PIMCORE_OPENSEARCH_DSN` env var with the format:
 * opensearch://user:pass@host:port?ssl_verify=bool
 *
 * @internal
 */
final readonly class OpenSearchEnvVarDefinition extends AbstractSearchEngineEnvVarDefinition
{
    public function getKey(): string
    {
        return 'opensearch';
    }

    public function getLabel(): string
    {
        return 'OpenSearch';
    }

    public function getSectionName(): string
    {
        return 'pimcore/opensearch-client';
    }

    protected function getScheme(): string
    {
        return 'opensearch';
    }

    protected function getEnvVarName(): string
    {
        return 'PIMCORE_OPENSEARCH_DSN';
    }

    protected function getDefaultDsn(): string
    {
        return 'opensearch://admin@localhost:9200?ssl_verify=false';
    }

    protected function getDefaultPort(): int
    {
        return 9200;
    }

    protected function getDefaultSslVerify(): bool
    {
        return false;
    }
}
