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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\DataObject\BlockDataMarshaller;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Pimcore;
use Pimcore\Element\MarshallerService;
use Pimcore\Logger;
use Pimcore\Marshaller\MarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\AfterDecryptionUnmarshallerInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\BeforeEncryptionMarshallerInterface;

/**
 * @internal
 */
class EncryptedField implements MarshallerInterface
{
    protected MarshallerService $marshallerService;

    /**
     * EncryptedField constructor.
     *
     */
    public function __construct(MarshallerService $marshallerService)
    {
        $this->marshallerService = $marshallerService;
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            if ($this->marshallerService->supportsFielddefinition('block', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $delegateFd->getFieldtype());
                $value = $marshaller->marshal($value, ['fieldDefinition' => $delegateFd, 'format' => 'block']);
            }
            $encryptedValue = $this->encrypt($value, $params);

            return $encryptedValue;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            $decryptedValue = $this->decrypt($value, $params);

            if ($this->marshallerService->supportsFielddefinition('block', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('block', $delegateFd->getFieldtype());

                $decryptedValue = $marshaller->unmarshal($decryptedValue, ['fieldDefinition' => $delegateFd, 'format' => 'block']);
            }

            return $decryptedValue;
        }

        return null;
    }

    /**
     *
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function encrypt(mixed $data, array $params = []): string
    {
        if (!is_null($data)) {
            $object = $params['object'] ?? null;

            $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

            try {
                $key = Key::loadFromAsciiSafeString($key);
            } catch (Exception $e) {
                throw new Exception('could not load key');
            }
            // store it in raw binary mode to preserve space

            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            if ($delegateFd instanceof BeforeEncryptionMarshallerInterface) {
                $data = $delegateFd->marshalBeforeEncryption($data, $object, $params);
            }

            $data = Crypto::encrypt((string)$data, $key);
        }

        return $data;
    }

    /**
     *
     *
     * @throws Exception
     */
    public function decrypt(?string $data, array $params = []): ?string
    {
        if ($data) {
            $object = $params['object'] ?? null;
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            try {
                $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

                try {
                    $key = Key::loadFromAsciiSafeString($key);
                } catch (Exception $e) {
                    throw new Exception('could not load key');
                }

                if (!(isset($params['skipDecryption']) && $params['skipDecryption'])) {
                    $data = Crypto::decrypt($data, $key);
                }

                if ($delegateFd instanceof AfterDecryptionUnmarshallerInterface) {
                    $data = $delegateFd->unmarshalAfterDecryption($data, $object, $params);
                }

                return $data;
            } catch (Exception $e) {
                Logger::error((string) $e);

                throw new Exception('encrypted field ' . $delegateFd->getName() . ' cannot be decoded');
            }
        }

        return null;
    }
}
