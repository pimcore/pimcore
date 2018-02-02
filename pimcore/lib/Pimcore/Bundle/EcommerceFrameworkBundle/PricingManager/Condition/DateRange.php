<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Condition;

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment;

class DateRange implements IDateRange
{
    /**
     * @var \DateTime
     */
    protected $starting;

    /**
     * @var \DateTime
     */
    protected $ending;

    /**
     * @param IEnvironment $environment
     *
     * @return bool
     */
    public function check(IEnvironment $environment)
    {
        if ($this->getStarting() && $this->getEnding()) {
            return $this->getStarting()->getTimestamp() < time() && $this->getEnding()->getTimestamp() > time();
        }

        return false;
    }

    /**
     * @param \DateTime $date
     *
     * @return IDateRange
     */
    public function setStarting(\DateTime $date)
    {
        $this->starting = $date;

        return $this;
    }

    /**
     * @param \DateTime $date
     *
     * @return IDateRange
     */
    public function setEnding(\DateTime $date)
    {
        $this->ending = $date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStarting()
    {
        return $this->starting;
    }

    /**
     * @return \DateTime
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
            'starting' => $this->getStarting()->getTimestamp(),
            'ending' => $this->getEnding()->getTimestamp()
        ]);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $starting = \DateTime::createFromFormat('Y-m-d\TH:i:s', $json->starting);
        $starting->setTime(0, 0, 0);

        $ending = \DateTime::createFromFormat('Y-m-d\TH:i:s', $json->ending);
        $ending->setTime(23, 59, 59);

        $this->setStarting($starting);
        $this->setEnding($ending);

        return $this;
    }
}
