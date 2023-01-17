<?php

namespace Pimcore\Bundle\StaticRoutesBundle\EventListener;

use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;
use Pimcore\Event\Admin\IndexActionSettingsEvent;

class IndexSettingsListener
{
    public function indexSettings(IndexActionSettingsEvent $settingsEvent): void
    {
        $settingsEvent->addSetting('staticroutes-writeable', (new Staticroute())->isWriteable());
    }
}
