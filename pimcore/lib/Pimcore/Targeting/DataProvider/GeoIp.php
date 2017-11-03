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

use GeoIp2\Database\Reader;
use GeoIp2\ProviderInterface;
use Pimcore\Targeting\Model\VisitorInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class GeoIp implements DataProviderInterface
{
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
    public function getKey(): string
    {
        return 'geoip';
    }

    /**
     * @inheritDoc
     */
    public function load(Request $request, VisitorInfo $visitorInfo)
    {
        $ip = $request->getClientIp();
        if (!$this->isPublicIp($ip)) {
            return;
        }

        $visitorInfo->set(
            $this->getKey(),
            $this->geoIpProvider->city($ip)
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
