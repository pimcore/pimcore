<?php

namespace Pimcore\Bundle\PimcoreBundle\Templating\Helper;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Templating\Helper\Helper;

class Glossary extends Helper implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'glossary';
    }

    public function start()
    {
        $this->logger->warning('Glossary helper is not implemented - ' . __METHOD__);
    }

    public function stop()
    {
        $this->logger->warning('Glossary helper is not implemented - ' . __METHOD__);
    }
}
