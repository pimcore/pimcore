<?php
declare(strict_types=1);

namespace Pimcore\Bundle\CustomReportsBundle\Tool;

interface HumanReadableElementNameInterface
{
    /**
     * Return a human readable element name
     *
     * Allow developers to define what will be returned in the report
     *
     * @return string
     */
    public function getHumanReadableElementName(): string;
}
