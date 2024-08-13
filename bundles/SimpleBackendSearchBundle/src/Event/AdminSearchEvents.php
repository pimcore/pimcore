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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Event;

final class AdminSearchEvents
{
    /**
     * Fired before the request params are parsed.
     *
     * Subject: \Pimcore\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SEARCH_LIST_BEFORE_FILTER_PREPARE = 'pimcore.admin.search.list.beforeFilterPrepare';

    /**
     * Allows you to modify the search backend list before it is loaded.
     *
     * Subject: \Pimcore\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | the search backend list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SEARCH_LIST_BEFORE_LIST_LOAD = 'pimcore.admin.search.list.beforeListLoad';

    /**
     * Allows you to modify the the result after the list was loaded.
     *
     * Subject: \Pimcore\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const SEARCH_LIST_AFTER_LIST_LOAD = 'pimcore.admin.search.list.afterListLoad';

    /**
     * Allows you to modify the search backend list before it is loaded.
     *
     * Subject: \Pimcore\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | the search backend list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const QUICKSEARCH_LIST_BEFORE_LIST_LOAD = 'pimcore.admin.quickSearch.list.beforeListLoad';

    /**
     * Allows you to modify the the result after the list was loaded.
     *
     * Subject: \Pimcore\Bundle\SimpleBackendSearchBundle\Controller\SearchController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    const QUICKSEARCH_LIST_AFTER_LIST_LOAD = 'pimcore.admin.quickSearch.list.afterListLoad';
}
