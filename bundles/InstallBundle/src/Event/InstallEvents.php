<?php

namespace Pimcore\Bundle\InstallBundle\Event;

class InstallEvents
{
    /**
     * Event gets fire for every installer step e.g. install assets, install db
     */
    public const EVENT_NAME_STEP = 'pimcore.installer.step';

    /**
     * Event is fired before bundle selection in installer. Bundles and Recommendations can be added or removed here
     */
    public const EVENT_BUNDLE_SETUP = 'pimcore.installer.setup_bundles';
}
