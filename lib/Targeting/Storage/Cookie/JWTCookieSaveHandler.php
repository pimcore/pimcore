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

namespace Pimcore\Targeting\Storage\Cookie;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use Pimcore\Targeting\Storage\Cookie\JWT\Decoder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class JWTCookieSaveHandler extends AbstractCookieSaveHandler
{
    const CLAIM_TARGETING_DATA = 'ptg';

    /**
     * @var string
     */
    private $secret;

    /**
     * @var Signer
     */
    private $signer;

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

        $this->secret = $secret;
        $this->signer = $signer ?? new Sha256();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @inheritdoc
     */
    protected function parseData(string $scope, string $name, $data): array
    {
        if (null === $data) {
            return [];
        }

        $parser = new Parser(new Decoder());

        try {
            $token = $parser->parse($data);

            $data = new ValidationData();

            // validate token (expiry, ...)
            if (!$token->validate($data)) {
                return [];
            }

            // verify token signature
            if (!$token->verify($this->signer, $this->secret)) {
                return [];
            }
        } catch (\Throwable $e) {
            $this->logger->error($e);

            return [];
        }

        $data = $token->getClaim(self::CLAIM_TARGETING_DATA, []);

        if (!is_array($data)) {
            $data = [];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    protected function prepareData(string $scope, string $name, $expire, $data)
    {
        if (empty($data)) {
            return null;
        }

        $builder = $this->createTokenBuilder($scope, $name, $expire, $data);
        $token = $builder->getToken();
        $result = (string)$token;

        return $result;
    }

    protected function createTokenBuilder(string $scope, string $name, $expire, $data): Builder
    {
        $time = time();

        $builder = new Builder();
        $builder
            ->setIssuedAt($time)
            ->set(self::CLAIM_TARGETING_DATA, $data);

        if (0 === $expire) {
            $builder->setExpiration($time + (60 * 30)); // expire in 30 min
        } elseif (is_int($expire) && $expire > 0) {
            $builder->setExpiration($expire);
        } elseif ($expire instanceof \DateTimeInterface) {
            $builder->setExpiration($expire->getTimestamp());
        }

        $builder->sign($this->signer, $this->secret);

        return $builder;
    }
}
