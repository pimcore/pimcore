<?php

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
    /**
     * @var \DateTime|null
     */
    protected $starting;

    /**
     * @var \DateTime|null
     */
    protected $ending;

    /**
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment)
    {
        if ($this->getStarting() && $this->getEnding()) {
            return $this->getStarting()->getTimestamp() < time() && $this->getEnding()->getTimestamp() > time();
        }

        return false;
    }

    /**
     * @param \DateTime $date
     *
     * @return DateRangeInterface
     */
    public function setStarting(\DateTime $date)
    {
        $this->starting = $date;

        return $this;
    }

    /**
     * @param \DateTime $date
     *
     * @return DateRangeInterface
     */
    public function setEnding(\DateTime $date)
    {
        $this->ending = $date;

        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getStarting()
    {
        return $this->starting;
    }

    /**
     * @return \DateTime|null
     */
    public function getEnding()
    {
        return $this->ending;
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        return json_encode([
            'type' => 'DateRange',
            'starting' => $this->getStarting()->format('d.m.Y'),
            'ending' => $this->getEnding()->format('d.m.Y'),
        ]);
    }

    /**
     * @param string $string
     *
     * @return ConditionInterface
     */
    public function fromJSON($string)
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
