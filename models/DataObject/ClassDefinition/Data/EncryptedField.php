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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Class EncryptedField
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 *
 * How to generate a key: vendor/bin/generate-defuse-key
 */
class EncryptedField extends Data implements ResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use Extension\ColumnType;

    /**
     * don't throw an error it encrypted field cannot be decoded (default)
     */
    const STRICT_DISABLED = 0;

    /**
     * throw an error it encrypted field cannot be decoded (default)
     */
    const STRICT_ENABLED = 1;

    /**
     * @var int
     */
    private static $strictMode = self::STRICT_ENABLED;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'encryptedField';

    /**
     * @internal
     *
     * @var string
     */
    public $delegateDatatype;

    /**
     * @internal
     *
     * @var Model\DataObject\ClassDefinition\Data|null
     */
    public $delegate;

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'LONGBLOB';

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if ($data) {
            /** @var ResourcePersistenceAwareInterface|null $fd */
            $fd = $this->getDelegateDatatypeDefinition();
            if ($fd) {
                $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : $data;
                $result = $fd->getDataForResource($data, $object, $params);
                if (isset($params['skipEncryption']) && $params['skipEncryption']) {
                    return $result;
                }

                return $this->encrypt($result, $object, $params);
            }
        }

        return null;
    }

    /**
     * @param mixed $data
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     *
     * @throws \Defuse\Crypto\Exception\BadFormatException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function encrypt($data, $object, $params = [])
    {
        if (!is_null($data)) {
            $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

            try {
                $key = Key::loadFromAsciiSafeString($key);
            } catch (\Exception $e) {
                throw new \Exception('could not load key');
            }
            // store it in raw binary mode to preserve space
            if (method_exists($this->delegate, 'marshalBeforeEncryption')) {
                $data = $this->delegate->marshalBeforeEncryption($data, $object, $params);
            }

            $rawBinary = (isset($params['asString']) && $params['asString']) ? false : true;

            $data = Crypto::encrypt((string)$data, $key, $rawBinary);
        }

        return $data;
    }

    /**
     * @param string|null $data
     * @param Model\DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *
     * @throws \Exception
     */
    private function decrypt($data, $object, $params = [])
    {
        if ($data) {
            try {
                $key = \Pimcore::getContainer()->getParameter('pimcore.encryption.secret');
                try {
                    $key = Key::loadFromAsciiSafeString($key);
                } catch (\Exception $e) {
                    if (!self::isStrictMode()) {
                        Logger::error('failed to load key');

                        return null;
                    }
                    throw new \Exception('could not load key');
                }

                $rawBinary = (isset($params['asString']) && $params['asString']) ? false : true;

                if (!(isset($params['skipDecryption']) && $params['skipDecryption'])) {
                    $data = Crypto::decrypt($data, $key, $rawBinary);
                }

                if (method_exists($this->delegate, 'unmarshalAfterDecryption')) {
                    $data = $this->delegate->unmarshalAfterDecryption($data, $object, $params);
                }

                return $data;
            } catch (\Exception $e) {
                Logger::error($e);
                if (self::isStrictMode()) {
                    throw new \Exception('encrypted field ' . $this->getName() . ' cannot be decoded');
                }
            }
        }

        return null;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\EncryptedField|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        /** @var ResourcePersistenceAwareInterface|null $fd */
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $this->decrypt($data, $object, $params);
            $data = $fd->getDataFromResource($data, $object, $params);

            $field = new Model\DataObject\Data\EncryptedField($this->delegate, $data);

            if (isset($params['owner'])) {
                $field->_setOwner($params['owner']);
                $field->_setOwnerFieldname($params['fieldname']);
                $field->_setOwnerLanguage($params['language'] ?? null);
            }

            return $field;
        }

        return null;
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param mixed $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : $data;
            $result = $fd->getDataForEditmode($data, $object, $params);

            return $result;
        }

        return null;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return Model\DataObject\Data\EncryptedField|null
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $result = $fd->getDataFromEditmode($data, $object, $params);
            $result = new Model\DataObject\Data\EncryptedField($this->delegate, $result);

            return $result;
        }

        return null;
    }

    /**
     * @param float $data
     * @param Model\DataObject\Concrete $object
     * @param mixed $params
     *
     * @return float
     */
    public function getDataFromGridEditor($data, $object = null, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd && method_exists($fd, 'getDataFromGridEditor')) {
            $data = $fd->getDataFromGridEditor($data, $object, $params);
            $data = new Model\DataObject\Data\EncryptedField($this->delegate, $data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : $data;
            $fd->checkValidity($data, $omitMandatoryCheck);
        }
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data\EncryptedField $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Model\DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->datatype = $masterDefinition->datatype;
    }

    /**
     * @param Model\DataObject\Data\EncryptedField|null $data
     *
     * @return bool
     */
    public function isEmpty($data)
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : $data;

            return $fd->isEmpty($data);
        }

        return true;
    }

    /**
     * display the quantity value field data in the grid
     *
     * @param mixed $data
     * @param Model\DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array
     */
    public function getDataForGrid($data, $object = null, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            if (method_exists($fd, 'getDataForGrid')) {
                $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;

                return $fd->getDataForGrid($data, $object, $params);
            }
        }

        return $data;
    }

    /**
     * @param Model\DataObject\Data\EncryptedField|null $data
     * @param Model\DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;

        return $fd->getVersionPreview($data, $object, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getForCsvExport($object, $params = [])
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $this->getDataFromObjectParam($object, $params);
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;

            if (is_array($params)) {
                $params = [];
            }
            $params['injectedData'] = $data;

            return $fd->getForCsvExport($object, $params);
        }

        return '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     * @param  mixed $value
     * @param  string $operator
     * @param  array $params
     *
     * @return string
     *
     */
    public function getFilterCondition($value, $operator, $params = [])
    {
        return '';
    }

    /**
     * @return string
     */
    public function getDelegateDatatype()
    {
        return $this->delegateDatatype;
    }

    /**
     * @param string $delegateDatatype
     */
    public function setDelegateDatatype($delegateDatatype)
    {
        $this->delegateDatatype = $delegateDatatype;
    }

    /**
     * @return Model\DataObject\ClassDefinition\Data|null
     */
    public function getDelegateDatatypeDefinition()
    {
        return $this->getDelegate();
    }

    /**
     * @internal
     *
     * @param mixed $data
     */
    public function setupDelegate($data)
    {
        $this->delegate = null;

        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');
        if ($this->getDelegateDatatype()) {
            if ($loader->supports($this->getDelegateDatatype())) {
                $delegate = $loader->build($this->getDelegateDatatype());
                $className = get_class($delegate);
                if (method_exists($className, '__set_state')) {
                    $delegate = $className::__set_state($data);
                }
                $this->delegate = $delegate;
            }
        }
    }

    /**
     * @return int
     */
    public static function isStrictMode()
    {
        return self::$strictMode;
    }

    /**
     * @param int $strictMode
     */
    public static function setStrictMode($strictMode)
    {
        self::$strictMode = $strictMode;
    }

    /**
     * @return Model\DataObject\ClassDefinition\Data|null
     */
    public function getDelegate()
    {
        return $this->delegate;
    }

    /**
     * @param Model\DataObject\ClassDefinition\Data|null $delegate
     */
    public function setDelegate($delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * @param Model\DataObject\Concrete $object
     * @param array $context
     *
     * @return self
     */
    public function enrichLayoutDefinition($object, $context = [])
    {
        $delegate = $this->getDelegate();

        if (method_exists($delegate, 'enrichLayoutDefinition')) {
            $delegate->enrichLayoutDefinition($object, $context);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        // encrypted data shouldn't be in search index
        return '';
    }

    /**
     * @param Model\DataObject\Data\EncryptedField|null $oldValue
     * @param Model\DataObject\Data\EncryptedField|null $newValue
     *
     * @return bool
     */
    public function isEqual($oldValue, $newValue): bool
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd instanceof EqualComparisonInterface) {
            $oldValue = $oldValue instanceof Model\DataObject\Data\EncryptedField ? $oldValue->getPlain() : null;
            $newValue = $newValue instanceof Model\DataObject\Data\EncryptedField ? $newValue->getPlain() : null;

            return $fd->isEqual($oldValue, $newValue);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return $this->delegate ? $this->delegate->getPhpdocInputType() . '|\\Pimcore\\Model\\DataObject\\Data\\EncryptedField' : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return $this->delegate ? $this->delegate->getPhpdocReturnType() . '|\\Pimcore\\Model\\DataObject\\Data\\EncryptedField' : null;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($value, $params = [])
    {
        if ($value instanceof Model\DataObject\Data\EncryptedField) {
            $plainValue = $value->getPlain();
            if ($this->delegate instanceof NormalizerInterface) {
                $plainValue = $this->delegate->normalize($plainValue, $params);
            }

            return $plainValue;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($value, $params = [])
    {
        if ($this->delegate instanceof NormalizerInterface) {
            $value = $this->delegate->denormalize($value, $params);
        }
        $value = new Model\DataObject\Data\EncryptedField($this->delegate, $value);

        return $value;
    }
}
