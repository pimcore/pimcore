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

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Exception;
use Pimcore;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

/**
 * Class EncryptedField
 *
 * @package Pimcore\Model\DataObject\ClassDefinition\Data
 *
 * How to generate a key: vendor/bin/generate-defuse-key
 */
class EncryptedField extends Data implements ResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, LayoutDefinitionEnrichmentInterface
{
    /**
     * don't throw an error it encrypted field cannot be decoded (default)
     */
    const STRICT_DISABLED = 0;

    /**
     * throw an error it encrypted field cannot be decoded (default)
     */
    const STRICT_ENABLED = 1;

    private static int $strictMode = self::STRICT_ENABLED;

    /**
     * @internal
     *
     */
    public string $delegateDatatype;

    /**
     * @internal
     *
     * @var Model\DataObject\ClassDefinition\Data|array|null
     */
    public Data|array|null $delegate = null;

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): mixed
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
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     */
    private function encrypt(mixed $data, Model\DataObject\Concrete $object = null, array $params): ?string
    {
        if (!is_null($data)) {
            $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

            try {
                $key = Key::loadFromAsciiSafeString($key);
            } catch (Exception $e) {
                throw new Exception('Could not find config "pimcore.encryption.secret". Please run "vendor/bin/generate-defuse-key" from command line and add the result to config/config.yaml');
            }
            // store it in raw binary mode to preserve space
            if ($this->delegate instanceof BeforeEncryptionMarshallerInterface || method_exists($this->delegate, 'marshalBeforeEncryption')) {
                $data = $this->delegate->marshalBeforeEncryption($data, $object, $params);
            }

            $rawBinary = (isset($params['asString']) && $params['asString']) ? false : true;

            $data = Crypto::encrypt((string)$data, $key, $rawBinary);
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    private function decrypt(?string $data, Model\DataObject\Concrete $object = null, array $params): ?string
    {
        if ($data) {
            try {
                $key = Pimcore::getContainer()->getParameter('pimcore.encryption.secret');

                try {
                    $key = Key::loadFromAsciiSafeString($key);
                } catch (Exception $e) {
                    if (!self::isStrictMode()) {
                        Logger::error('failed to load key');

                        return null;
                    }

                    throw new Exception('could not load key');
                }

                $rawBinary = (isset($params['asString']) && $params['asString']) ? false : true;

                if (!(isset($params['skipDecryption']) && $params['skipDecryption'])) {
                    $data = Crypto::decrypt($data, $key, $rawBinary);
                }

                if ($this->delegate instanceof AfterDecryptionUnmarshallerInterface || method_exists($this->delegate, 'unmarshalAfterDecryption')) {
                    $data = $this->delegate->unmarshalAfterDecryption($data, $object, $params);
                }

                return $data;
            } catch (Exception $e) {
                Logger::error((string) $e);
                if (self::isStrictMode()) {
                    throw new Exception('encrypted field ' . $this->getName() . ' cannot be decoded');
                }
            }
        }

        return null;
    }

    /**
     * @param null|Model\DataObject\Concrete $object
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\EncryptedField
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
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
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
     * @param null|Model\DataObject\Concrete $object
     *
     * @see Data::getDataFromEditmode
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?Model\DataObject\Data\EncryptedField
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $result = $fd->getDataFromEditmode($data, $object, $params);
            $result = new Model\DataObject\Data\EncryptedField($this->delegate, $result);

            return $result;
        }

        return null;
    }

    public function getDataFromGridEditor(float $data, Model\DataObject\Concrete $object = null, array $params = []): float|Model\DataObject\Data\EncryptedField
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd && method_exists($fd, 'getDataFromGridEditor')) {
            $data = $fd->getDataFromGridEditor($data, $object, $params);
            $data = new Model\DataObject\Data\EncryptedField($this->delegate, $data);
        }

        return $data;
    }

    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : $data;
            $fd->checkValidity($data, $omitMandatoryCheck);
        }
    }

    public function isEmpty(mixed $data): bool
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
     *
     */
    public function getDataForGrid(mixed $data, Model\DataObject\Concrete $object = null, array $params = []): array
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            if (method_exists($fd, 'getDataForGrid')) {
                $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;

                return $fd->getDataForGrid($data, $object, $params);
            }
        }

        return $data ?? [];
    }

    /**
     * @param Model\DataObject\Concrete|null $object
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        $fd = $this->getDelegateDatatypeDefinition();
        $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;

        return $fd->getVersionPreview($data, $object, $params);
    }

    public function getForCsvExport(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd) {
            $data = $this->getDataFromObjectParam($object, $params);
            $data = $data instanceof Model\DataObject\Data\EncryptedField ? $data->getPlain() : null;
            $params['injectedData'] = $data;

            return $fd->getForCsvExport($object, $params);
        }

        return '';
    }

    /**
     * returns sql query statement to filter according to this data types value(s)
     *
     *
     *
     */
    public function getFilterCondition(mixed $value, string $operator, array $params = []): string
    {
        return '';
    }

    public function getDelegateDatatype(): string
    {
        return $this->delegateDatatype;
    }

    public function setDelegateDatatype(string $delegateDatatype): void
    {
        $this->delegateDatatype = $delegateDatatype;
    }

    public function getDelegateDatatypeDefinition(): Data|array|null
    {
        return $this->getDelegate();
    }

    /**
     * @internal
     *
     */
    public function setupDelegate(mixed $data): void
    {
        $this->delegate = null;

        $loader = Pimcore::getContainer()->get('pimcore.implementation_loader.object.data');
        if ($this->getDelegateDatatype()) {
            if ($loader->supports($this->getDelegateDatatype())) {
                $delegate = $loader->build($this->getDelegateDatatype());
                $className = get_class($delegate);
                $delegate = $className::__set_state($data);
                $this->delegate = $delegate;
            }
        }
    }

    public static function isStrictMode(): int
    {
        return self::$strictMode;
    }

    public static function setStrictMode(int $strictMode): void
    {
        self::$strictMode = $strictMode;
    }

    public function getDelegate(): Data|array|null
    {
        return $this->delegate;
    }

    public function setDelegate(Data|array|null $delegate): void
    {
        $this->delegate = $delegate;
    }

    public function enrichLayoutDefinition(?Concrete $object, array $context = []): static
    {
        $delegate = $this->getDelegate();

        if ($delegate instanceof LayoutDefinitionEnrichmentInterface) {
            $delegate->enrichLayoutDefinition($object, $context);
        }

        return $this;
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        // encrypted data shouldn't be in search index
        return '';
    }

    public function isEqual(mixed $oldValue, mixed $newValue): bool
    {
        $fd = $this->getDelegateDatatypeDefinition();
        if ($fd instanceof EqualComparisonInterface) {
            $oldValue = $oldValue instanceof Model\DataObject\Data\EncryptedField ? $oldValue->getPlain() : null;
            $newValue = $newValue instanceof Model\DataObject\Data\EncryptedField ? $newValue->getPlain() : null;

            return $fd->isEqual($oldValue, $newValue);
        }

        return false;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return null;
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return null;
    }

    public function getPhpdocInputType(): ?string
    {
        return $this->delegate ? $this->delegate->getPhpdocInputType() . '|\\Pimcore\\Model\\DataObject\\Data\\EncryptedField' : null;
    }

    public function getPhpdocReturnType(): ?string
    {
        return $this->delegate ? $this->delegate->getPhpdocReturnType() . '|\\Pimcore\\Model\\DataObject\\Data\\EncryptedField' : null;
    }

    public function normalize(mixed $value, array $params = []): mixed
    {
        if ($value instanceof Model\DataObject\Data\EncryptedField) {
            $plainValue = $value->getPlain();
            if ($this->delegate instanceof NormalizerInterface) {
                $plainValue = $this->delegate->normalize($plainValue, $params);
            }

            return $plainValue;
        }

        return null;
    }

    public function denormalize(mixed $value, array $params = []): Model\DataObject\Data\EncryptedField
    {
        if ($this->delegate instanceof NormalizerInterface) {
            $value = $this->delegate->denormalize($value, $params);
        }
        $value = new Model\DataObject\Data\EncryptedField($this->delegate, $value);

        return $value;
    }

    public function getColumnType(): string
    {
        return 'LONGBLOB';
    }

    public function getFieldType(): string
    {
        return 'encryptedField';
    }
}
