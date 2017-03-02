<?php

namespace Pimcore\Event;

final class SearchBackendEvents
{
    /**
     * @Event("Pimcore\Event\Model\SearchBackendEvent")
     * @var string
     */
    const PRE_SAVE = 'pimcore.search.backend.preSave';

    /**
     * @Event("Pimcore\Event\Model\SearchBackendEvent")
     * @var string
     */
    const POST_SAVE = 'pimcore.search.backend.postSave';
}
