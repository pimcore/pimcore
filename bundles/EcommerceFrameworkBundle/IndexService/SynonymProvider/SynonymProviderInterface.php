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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\SynonymProvider;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\ElasticSearch;

interface SynonymProviderInterface
{
    /**
     * Get synonyms, depending on the format that is specified in the filter.
     * Typically Solr is used, compare https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis-synonym-tokenfilter.html.
     * Examples:
     *      - line 1: i-pod, i pod => ipod
     *      - line 2: sea biscuit, sea biscit => seabiscuit
     *      - ...
     *
     * @return string[] an array, where each array element corresponds to one line of related synonyms.
     */
    public function getSynonyms(): array;

    /**
     * return a list of options that can be configured per options provider and can be used for the
     * implementation of the synonym provider.
     *
     * @return array
     */
    public function getOptions(): array;
}
