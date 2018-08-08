<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\EcommerceFrameworkBundle\VoucherService\TokenManager;

use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractVoucherTokenType;

class TokenManagerFactory implements ITokenManagerFactory
{
    /**
     * @var array
     */
    private $mapping;

    /**
     * @var ITokenManager[]
     */
    private $tokenManagers = [];

    /**
     * @param array $mapping
     */
    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getTokenManager(AbstractVoucherTokenType $configuration): ITokenManager
    {
        $id   = $configuration->getObject()->getId();
        $type = $configuration->getType();

        if (isset($this->tokenManagers[$id])) {
            return $this->tokenManagers[$id];
        }

        if (!isset($this->mapping[$type])) {
            throw new InvalidConfigException(sprintf('Token Manager for type %s is not defined.', $type));
        }

        $tokenManagerClass = $this->mapping[$type];
        $this->tokenManagers[$id] = new $tokenManagerClass($configuration);

        return $this->tokenManagers[$id];
    }
}
