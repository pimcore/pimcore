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

use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ConditionInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\EnvironmentInterface;

class ClientIp implements ConditionInterface
{
    /**
     * @var int
     */
    protected $ip;

    /**
     * @param EnvironmentInterface $environment
     *
     * @return bool
     */
    public function check(EnvironmentInterface $environment)
    {
        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?: $_SERVER['REMOTE_ADDR'];

        return $clientIp == $this->getIp();
    }

    /**
     * @return string
     */
    public function toJSON()
    {
        // basic
        $json = [
            'type' => 'ClientIp', 'ip' => $this->getIp(),
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return ConditionInterface
     */
    public function fromJSON($string)
    {
        $json = json_decode($string);

        $this->setIp($json->ip);

        return $this;
    }

    /**
     * @return int
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param int $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }
}
