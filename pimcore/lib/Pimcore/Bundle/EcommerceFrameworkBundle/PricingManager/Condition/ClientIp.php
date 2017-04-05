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

class ClientIp implements \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
{
    /**
     * @var int
     */
    protected $ip;


    /**
     * @param \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment
     *
     * @return boolean
     */
    public function check(\Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\IEnvironment $environment)
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
            'type' => 'ClientIp'
            , 'ip' => $this->getIp()
        ];

        return json_encode($json);
    }

    /**
     * @param string $string
     *
     * @return \Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\ICondition
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
