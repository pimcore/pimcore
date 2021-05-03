<?php

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

namespace Pimcore\DataObject\ClassificationstoreDataMarshaller;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Pimcore\Element\MarshallerService;
use Pimcore\Logger;
use Pimcore\Marshaller\MarshallerInterface;

/**
 * @internal
 */
class EncryptedField implements MarshallerInterface
{
    /**
     * @var MarshallerService
     */
    protected $marshallerService;

    /**
     * Localizedfields constructor.
     *
     * @param MarshallerService $marshallerService
     */
    public function __construct(MarshallerService $marshallerService)
    {
        $this->marshallerService = $marshallerService;
    }

    /**
     * {@inheritdoc}
     */
    public function marshal($value, $params = [])
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

    /**
     * {@inheritdoc}
     */
    public function unmarshal($value, $params = [])
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
     * @param mixed $data
     * @param array $params
     *
     * @return string
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    public function encrypt($data, $params = [])
    {
        if (!is_null($data)) {
            $object = $params['object'] ?? null;

            $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

            try {
                $key = Key::loadFromAsciiSafeString($key);
            } catch (\Exception $e) {
                throw new \Exception('could not load key');
            }
            // store it in raw binary mode to preserve space

            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            if (method_exists($delegateFd, 'marshalBeforeEncryption')) {
                $data = $delegateFd->marshalBeforeEncryption($data, $object, $params);
            }

            $data = Crypto::encrypt((string)$data, $key);
        }

        return $data;
    }

    /**
     * @param string|null $data
     * @param array $params
     *
     * @return string|null
     *
     * @throws \Exception
     */
    public function decrypt($data, $params = [])
    {
        if ($data) {
            $object = $params['object'] ?? null;
            $fd = $params['fieldDefinition'];
            $delegateFd = $fd->getDelegate();

            try {
                $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                try {
                    $key = Key::loadFromAsciiSafeString($key);
                } catch (\Exception $e) {
                    throw new \Exception('could not load key');
                }

                if (!(isset($params['skipDecryption']) && $params['skipDecryption'])) {
                    $data = Crypto::decrypt($data, $key);
                }

                if (method_exists($delegateFd, 'unmarshalAfterDecryption')) {
                    $data = $delegateFd->unmarshalAfterDecryption($data, $object, $params);
                }

                return $data;
            } catch (\Exception $e) {
                Logger::error($e);
                throw new \Exception('encrypted field ' . $delegateFd->getName() . ' cannot be decoded');
            }
        }

        return null;
    }
}
