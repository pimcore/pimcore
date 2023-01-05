<?php

namespace Pimcore\Bundle\CustomReportsBundle\EventListener;

use Pimcore\Event\Admin\IndexActionSettingsEvent;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;

class IndexSettingsListener
{
    public function indexSettings(IndexActionSettingsEvent $settingsEvent): void
    {
        $settingsEvent->addSetting('custom-reports-writeable', (new Config())->isWriteable());
    }
}
