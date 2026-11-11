<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Exception;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\StructuredValidationException;
use Pimcore\Model\Element\ValidationMessageKey;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class Password extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use CheckPasswordLengthTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\SimpleNormalizerTrait;

    private const EDITMODE_MASK = '******';

    public ?int $minimumLength = null;

    public function getMinimumLength(): ?int
    {
        return $this->minimumLength;
    }

    public function setMinimumLength(?int $minimumLength): void
    {
        $this->minimumLength = $minimumLength;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataForResource
     */
    public function getDataForResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (empty($data)) {
            return null;
        }

        $info = password_get_info($data);
        if ($info['algo'] !== null) {
            return $data;
        }

        $hashed = $this->calculateHash($data);

        /** set the hashed password back to the object, to be sure that is not plain-text after the first save
         this is especially to avoid plaintext passwords in the search-index see: PIMCORE-1406 */

        // a model should be switched if the owner parameter is used,
        // for example: field collections would use \Pimcore\Model\DataObject\Fieldcollection\Data\Dao
        $passwordModel = array_key_exists('owner', $params)
            ? $params['owner']
            : ($object ?: null);

        if (
            null !== $passwordModel &&
            !$passwordModel instanceof DataObject\Classificationstore &&
            !$passwordModel instanceof DataObject\Localizedfield
        ) {
            $setter = 'set' . ucfirst($this->getName());
            $passwordModel->$setter($hashed);
        }

        return $hashed;
    }

    /**
     * Calculate hash according to configured parameters
     *
     *
     *
     * @internal
     */
    public function calculateHash(string $data): string
    {
        $config = Config::getSystemConfiguration()['security']['password'];

        return password_hash($data, $config['algorithm'], $config['options']);
    }

    /**
     * Verify password. Optionally re-hash the password if needed.
     *
     * Re-hash will be performed if PHP's password_hash default params (algorithm, cost) differ
     * from the ones which were used to create the hash (e.g. cost was increased from 10 to 12).
     * In this case, the hash will be re-calculated with the new parameters and saved back to the object.
     *
     * @param bool|true $updateHash
     *
     * @internal
     */
    public function verifyPassword(string $password, DataObject\Concrete $object, bool $updateHash = true): bool
    {
        $getter = 'get' . ucfirst($this->getName());
        $setter = 'set' . ucfirst($this->getName());

        $objectHash = $object->$getter();
        if (empty($objectHash)) {
            return false;
        }

        $result = password_verify($password, $objectHash);

        if ($result && $updateHash) {
            $config = Config::getSystemConfiguration()['security']['password'];

            if (password_needs_rehash($objectHash, $config['algorithm'], $config['options'])) {
                $newHash = $this->calculateHash($password);

                $object->$setter($newHash);
                $object->save();
            }
        }

        return $result;
    }

    /**
     *
     *
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     */
    public function getDataFromResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     *
     *
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     */
    public function getDataForQueryResource(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    public function getDataForEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data === null || $data === '') {
            return null;
        }

        return self::EDITMODE_MASK;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?string
    {
        if ($data === '') {
            return null;
        }

        if ($data === self::EDITMODE_MASK) {
            // the placeholder means "unchanged": keep the stored value, never persist or clear it
            return $this->resolveStoredValue($object, $params);
        }

        return $data;
    }

    /**
     * Resolves the currently stored value of this field for the given owner/container context.
     * Fails safe: if it cannot be resolved the operation is aborted, so the placeholder is never
     * persisted as a password and an existing password is never cleared.
     */
    private function resolveStoredValue(?DataObject\Concrete $object, array $params): ?string
    {
        if (array_key_exists('injectedData', $params)) {
            // the caller already resolved the stored value (e.g. classification store)
            $injected = $this->unwrapEncrypted($params['injectedData']);

            return is_string($injected) && $injected !== '' ? $injected : null;
        }

        if ($object === null) {
            throw new Exception('Cannot keep the stored value of password field [ ' . $this->getName() . ' ] without an object');
        }

        $context = $params['context'] ?? [];
        $containerType = $context['containerType'] ?? null;
        $getter = 'get' . ucfirst((string) $this->getName());
        $value = null;

        if ($containerType === null) {
            if (!method_exists($object, $getter)) {
                throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
            }
            // localized fields are read per language
            $value = isset($params['language']) ? $object->$getter($params['language']) : $object->$getter();
        } elseif ($containerType === 'fieldcollection') {
            $containerGetter = 'get' . ucfirst((string) ($context['fieldname'] ?? ''));
            $oIndex = $context['oIndex'] ?? null;
            if ($oIndex === null) {
                // a newly added item has no stored value yet
                return null;
            }
            $container = method_exists($object, $containerGetter) ? $object->$containerGetter() : null;
            $item = $container instanceof DataObject\Fieldcollection ? ($container->getItems()[$oIndex] ?? null) : null;
            if ($item === null || !method_exists($item, $getter)) {
                throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
            }
            $value = isset($params['language']) ? $item->$getter($params['language']) : $item->$getter();
        } elseif ($containerType === 'objectbrick') {
            $containerGetter = 'get' . ucfirst((string) ($context['fieldname'] ?? ''));
            $brickGetter = 'get' . ucfirst((string) ($context['containerKey'] ?? ''));
            $container = method_exists($object, $containerGetter) ? $object->$containerGetter() : null;
            $brick = $container !== null && method_exists($container, $brickGetter) ? $container->$brickGetter() : null;
            if ($brick === null) {
                // a newly added brick has no stored value yet
                return null;
            }
            if (!method_exists($brick, $getter)) {
                throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
            }
            $value = isset($params['language']) ? $brick->$getter($params['language']) : $brick->$getter();
        } elseif ($containerType === 'block') {
            $oIndex = $context['oIndex'] ?? null;
            if ($oIndex === null) {
                // a newly added block item has no stored value yet
                return null;
            }
            $blockGetter = 'get' . ucfirst((string) ($context['fieldname'] ?? ''));
            if (!method_exists($object, $blockGetter)) {
                throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
            }
            $language = $params['language'] ?? null;
            $items = $object->$blockGetter($language);
            $row = is_array($items) ? ($items[$oIndex] ?? null) : null;
            $element = is_array($row) ? ($row[$this->getName()] ?? null) : null;
            $localizedElement = is_array($row) ? ($row['localizedfields'] ?? null) : null;

            if ($element instanceof DataObject\Data\BlockElement) {
                $value = $element->getData();
            } elseif ($language !== null && $localizedElement instanceof DataObject\Data\BlockElement
                && $localizedElement->getData() instanceof DataObject\Localizedfield) {
                // a localized password keeps its values per language below the localized fields of the row
                $value = $localizedElement->getData()->getItems()[$language][$this->getName()] ?? null;
            } else {
                throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
            }
        } else {
            // other containers: the stored value cannot be resolved reliably
            throw new Exception('Cannot resolve the stored value of password field [ ' . $this->getName() . ' ]');
        }

        $value = $this->unwrapEncrypted($value);

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * A password delegated to by an encrypted field is stored wrapped.
     */
    private function unwrapEncrypted(mixed $value): mixed
    {
        return $value instanceof DataObject\Data\EncryptedField ? $value->getPlain() : $value;
    }

    /**
     * @internal
     */
    public function getForCsvExport(DataObject\Concrete|DataObject\Localizedfield|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object, array $params = []): string
    {
        $value = $this->getDataFromObjectParam($object, $params);

        $value = $this->unwrapEncrypted($value);

        return is_string($value) && $value !== '' ? self::EDITMODE_MASK : '';
    }

    /**
     *
     *
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, ?DataObject\Concrete $object = null, array $params = []): string
    {
        return '******';
    }

    public function getDataForGrid(?string $data, Concrete $object, array $params = []): string
    {
        return '******';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    public function getDiffDataFromEditmode(array $data, ?DataObject\Concrete $object = null, array $params = []): mixed
    {
        return $data[0]['data'];
    }

    /** See parent class.
     *
     */
    public function getDiffDataForEditMode(mixed $data, ?DataObject\Concrete $object = null, array $params = []): ?array
    {
        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = !($this->isDiffChangeAllowed($object, $params));
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->getFieldType();

        if ($data) {
            $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
            // $diffdata["value"] = $data;
        }

        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result = [];
        $result[] = $diffdata;

        return $result;
    }

    public function getParameterTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getReturnTypeDeclaration(): ?string
    {
        return '?string';
    }

    public function getPhpdocInputType(): ?string
    {
        return 'string|null';
    }

    public function getPhpdocReturnType(): ?string
    {
        return 'string|null';
    }

    /**
     *
     * @throws Model\Element\ValidationException|Exception
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = []): void
    {
        if (is_string($data) && $this->isPasswordTooLong($data)) {
            throw (new StructuredValidationException('Value in field [ ' . $this->getName() . ' ] is too long'))
                ->setTranslation(
                    ValidationMessageKey::MAX_LENGTH,
                    [
                        'max' => PasswordHasherInterface::MAX_PASSWORD_LENGTH,
                    ],
                );
        }

        if (!$omitMandatoryCheck && ($this->getMinimumLength() && is_string($data) && strlen($data) < $this->getMinimumLength())) {
            throw (new StructuredValidationException(
                'Value in field [ ' . $this->getName() . ' ] is not at least ' . $this->getMinimumLength()
                    . ' characters'
            ))
                ->setTranslation(ValidationMessageKey::MIN_LENGTH, ['min' => $this->getMinimumLength()]);
        }

        parent::checkValidity($data, $omitMandatoryCheck, $params);
    }

    public function getColumnType(): string
    {
        return 'varchar(255)';
    }

    public function getQueryColumnType(): string
    {
        return $this->getColumnType();
    }

    public function getFieldType(): string
    {
        return 'password';
    }
}
