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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Targeting\Storage\Cookie;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JWTCookieSaveHandler extends AbstractCookieSaveHandler
{
    const CLAIM_TARGETING_DATA = 'ptg';

    /**
     * @var Configuration
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        string $secret,
        array $options = [],
        Signer $signer = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($options);

        $config = Configuration::forSymmetricSigner(
            $signer ?? new Sha256(),
            InMemory::plainText($secret)
        );
        $config->setValidationConstraints(new SignedWith($config->signer(), $config->verificationKey()));
        $this->config = $config;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData(string $scope, string $name, $data): array
    {
        if (null === $data) {
            return [];
        }

        try {
            /** @var Plain $token */
            $token = $this->config->parser()->parse($data);
            $validator = $this->config->validator();

            // validate token (expiry, ...)
            if (!$validator->validate($token, ...$this->config->validationConstraints())) {
                return [];
            }
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return [];
        }

        $data = $token->claims()->get(self::CLAIM_TARGETING_DATA, []);

        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareData(string $scope, string $name, $expire, $data)
    {
        if (empty($data)) {
            return null;
        }

        $builder = $this->createTokenBuilder($scope, $name, $expire, $data);
        $token = $builder->getToken($this->config->signer(), $this->config->signingKey());
        $result = $token->toString();

        return $result;
    }

    /**
     * @param string $scope
     * @param string $name
     * @param int|string|\DateTimeInterface $expire
     * @param array|null $data
     *
     * @return Builder
     *
     * @throws \Exception
     */
    protected function createTokenBuilder(string $scope, string $name, $expire, $data): Builder
    {
        $time = new \DateTimeImmutable();

        $builder = $this->config->builder();
        $builder
            ->issuedAt($time)
            ->withClaim(self::CLAIM_TARGETING_DATA, $data);

        if (0 === $expire) {
            $builder->expiresAt($time->modify('+30 minutes')); // expire in 30 min
        } elseif (is_int($expire) && $expire > 0) {
            $expire = new \DateTimeImmutable('@'. $expire);
            $builder->expiresAt($expire);
        } elseif ($expire instanceof \DateTimeInterface) {
            $expire = new \DateTimeImmutable('@'. $expire->getTimestamp());
            $builder->expiresAt($expire);
        }

        $builder->getToken($this->config->signer(), $this->config->signingKey());

        return $builder;
    }
}
