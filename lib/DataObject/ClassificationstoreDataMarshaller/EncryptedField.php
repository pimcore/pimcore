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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

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
     * Localizedfields constructor.
     *
     */
    public function __construct(MarshallerService $marshallerService)
    {
        $this->marshallerService = $marshallerService;
    }

    public function marshal(mixed $value, array $params = []): mixed
    {
        if ($value !== null) {
            $encryptedValue = null;
            $encryptedValue2 = null;

            if (is_array($value)) {
                /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField $fd */
                $fd = $params['fieldDefinition'];
                $delegateFd = $fd->getDelegate();

                if ($this->marshallerService->supportsFielddefinition('classificationstore', $delegateFd->getFieldtype())) {
                    $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('classificationstore', $delegateFd->getFieldtype());
                    $encodedData = $marshaller->marshal($value, ['fieldDefinition' => $delegateFd, 'format' => 'classificationstore']);

                    if (is_array($encodedData)) {
                        $encryptedValue = $this->encrypt($encodedData['value'], $params);
                        $encryptedValue2 = $this->encrypt($encodedData['value2'], $params);
                    }
                }
            } else {
                $encryptedValue = $this->encrypt($value, $params);
            }

            $result = [
                'value' => $encryptedValue,
                'value2' => $encryptedValue2,
            ];

            return $result;
        }

        return null;
    }

    public function unmarshal(mixed $value, array $params = []): mixed
    {
        if (is_array($value)) {
            /** @var \Pimcore\Model\DataObject\ClassDefinition\Data\EncryptedField $fd */
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();
            if ($this->marshallerService->supportsFielddefinition('classificationstore', $delegateFd->getFieldtype())) {
                $marshaller = $this->marshallerService->buildFieldefinitionMarshaller('classificationstore', $delegateFd->getFieldtype());

                $encryptedValue = $this->decrypt($value['value'], $params);
                $encryptedValue2 = $this->decrypt($value['value2'], $params);

                $decodedData = $marshaller->unmarshal([
                    'value' => $encryptedValue,
                    'value2' => $encryptedValue2,
                ], ['fieldDefinition' => $delegateFd, 'format' => 'classificationstore']);

                return $decodedData;
            } else {
                $value = $this->decrypt($value['value'], $params);

                return $value;
            }
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
