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

namespace Pimcore\Targeting\DataProvider;

use GeoIp2\ProviderInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GeoIp implements DataProviderInterface
{
    const PROVIDER_KEY = 'geoip';

    /**
     * @var ProviderInterface
     */
    private $geoIpProvider;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var Ip
     */
    private $constraint;

    public function __construct(
        ProviderInterface $geoIpProvider,
        ValidatorInterface $validator
    )
    {
        $this->validator     = $validator;
        $this->geoIpProvider = $geoIpProvider;
    }

    /**
     * @inheritDoc
     */
    public function load(VisitorInfo $visitorInfo)
    {
        if ($visitorInfo->has(self::PROVIDER_KEY)) {
            return;
        }

        $result = null;

        $ip = $visitorInfo->getRequest()->getClientIp();
        if ($this->isPublicIp($ip)) {
            $result = $this->geoIpProvider->city($ip);
        }

        $visitorInfo->set(
            self::PROVIDER_KEY,
            $result
        );
    }

    private function isPublicIp(string $ip): bool
    {
        if (null === $this->constraint) {
            $this->constraint = new Ip([
                'version' => Ip::ALL_ONLY_PUBLIC
            ]);
        }

        $errors = $this->validator->validate($ip, $this->constraint);

        return $errors->count() === 0;
    }
}
