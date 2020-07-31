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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\V7\Payment\Config;

class HobexConfig
{
    protected $entityId = '';

    protected $authorizationBearer = '';

    protected $testSystem = false;

    protected $hostURL = '';

    protected $paymentMethods = [];

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     *
     * @return HobexConfig
     */
    public function setEntityId(string $entityId): HobexConfig
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationBearer(): string
    {
        return $this->authorizationBearer;
    }

    /**
     * @param string $authorizationBearer
     *
     * @return HobexConfig
     */
    public function setAuthorizationBearer(string $authorizationBearer): self
    {
        $this->authorizationBearer = $authorizationBearer;

        return $this;
    }

    /**
     * @return bool
     */
    public function isTestSystem(): bool
    {
        return $this->testSystem;
    }

    /**
     * @param bool $testSystem
     *
     * @return HobexConfig
     */
    public function setTestSystem(bool $testSystem): self
    {
        $this->testSystem = $testSystem;

        return $this;
    }

    /**
     * @return string
     */
    public function getHostURL(): string
    {
        return $this->hostURL;
    }

    /**
     * @param string $hostURL
     *
     * @return HobexConfig
     */
    public function setHostURL(string $hostURL): self
    {
        $this->hostURL = $hostURL;

        return $this;
    }

    /**
     * @return array
     */
    public function getPaymentMethods(): array
    {
        return $this->paymentMethods;
    }

    /**
     * @param string[] $paymentMethods
     *
     * @return HobexConfig
     */
    public function setPaymentMethods(array $paymentMethods): self
    {
        $this->paymentMethods = $paymentMethods;

        return $this;
    }
}
