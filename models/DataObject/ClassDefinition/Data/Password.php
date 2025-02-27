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

use Exception;
use Pimcore\Config;
use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;
use Symfony\Component\PasswordHasher\Hasher\CheckPasswordLengthTrait;

class Password extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use CheckPasswordLengthTrait;
    use DataObject\Traits\SimpleComparisonTrait;
    use DataObject\Traits\DataWidthTrait;
    use DataObject\Traits\SimpleNormalizerTrait;

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
        if ($info['algo'] !== null && $info['algo'] !== 0) {
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
        return $data;
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

        return $data;
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
            throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is too long');
        }

        if (!$omitMandatoryCheck && ($this->getMinimumLength() && is_string($data) && strlen($data) < $this->getMinimumLength())) {
            throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is not at least ' . $this->getMinimumLength() . ' characters');
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
