<?php

namespace Pimcore\Cache\Adapter;

interface PurgeAwareAdapterInterface
{
    /**
     * Do maintenance tasks - e.g. purge invalid items. This can take a long time and should only be called
     * for maintenance, not in code affecting the end user.
     */
    public function purge();
}
