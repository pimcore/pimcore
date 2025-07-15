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

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Event;

final class SearchBackendEvents
{
    /**
     * @Event("Pimcore\Bundle\SimpleBackendSearchBundle\Event\Model\SearchBackendEvent")
     *
     * @var string
     */
    const PRE_SAVE = 'pimcore.search.backend.preSave';

    /**
     * @Event("Pimcore\Bundle\SimpleBackendSearchBundle\Event\Model\SearchBackendEvent")
     *
     * @var string
     */
    const POST_SAVE = 'pimcore.search.backend.postSave';
}
