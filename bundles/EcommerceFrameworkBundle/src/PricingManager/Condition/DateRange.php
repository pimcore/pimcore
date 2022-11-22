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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use DateTimeZone;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class DateRange implements DateRangeInterface
{
    protected ?\DateTime $starting = null;

    protected ?\DateTime $ending = null;

    public function check(EnvironmentInterface $environment): bool
    {
        if ($this->getStarting() && $this->getEnding()) {
            return $this->getStarting()->getTimestamp() < time() && $this->getEnding()->getTimestamp() > time();
        }

        return false;
    }

    public function setStarting(\DateTime $date): DateRangeInterface
    {
        $this->starting = $date;

        return $this;
    }

    public function setEnding(\DateTime $date): DateRangeInterface
    {
        $this->ending = $date;

        return $this;
    }

    public function getStarting(): ?\DateTime
    {
        return $this->starting;
    }

    public function getEnding(): ?\DateTime
    {
        return $this->ending;
    }

    public function toJSON(): string
    {
        return json_encode([
            'type' => 'DateRange',
            'starting' => $this->getStarting()->format('d.m.Y'),
            'ending' => $this->getEnding()->format('d.m.Y'),
        ]);
    }

    public function fromJSON(string $string): ConditionInterface
    {
        $json = json_decode($string);

        $starting = \DateTime::createFromFormat('d.m.Y', $json->starting, new DateTimeZone('UTC'));
        $starting->setTime(0, 0, 0);

        $ending = \DateTime::createFromFormat('d.m.Y', $json->ending, new DateTimeZone('UTC'));
        $ending->setTime(23, 59, 59);

        $this->setStarting($starting);
        $this->setEnding($ending);

        return $this;
    }
}
