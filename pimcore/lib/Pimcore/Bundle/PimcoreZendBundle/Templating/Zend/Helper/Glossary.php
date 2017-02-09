<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating\Zend\Helper;

use Pimcore\Logger;
use Zend\View\Helper\AbstractHelper;

class Glossary extends AbstractHelper
{
    public function __invoke()
    {
        // TODO implement glossary helper
        return $this;
    }

    public function start()
    {
        Logger::warning('Glossary helper is not implemented - ' . __METHOD__);
    }

    public function stop()
    {
        Logger::warning('Glossary helper is not implemented - ' . __METHOD__);
    }
}
