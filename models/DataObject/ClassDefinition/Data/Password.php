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

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Normalizer\NormalizerInterface;

class Password extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;

    const HASH_FUNCTION_PASSWORD_HASH = 'password_hash';

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public string $fieldtype = 'password';

    /**
     * @internal
     *
     * @var string|int
     */
    public string|int $width = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'varchar(255)';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'varchar(255)';

    /**
     * @internal
     *
     * @var string
     */
    public string $algorithm = self::HASH_FUNCTION_PASSWORD_HASH;

    /**
     * @internal
     *
     * @var string
     */
    public string $salt = '';

    /**
     * @internal
     *
     * @var string
     */
    public string $saltlocation = '';

    public ?int $minimumLength = null;

    public function getWidth(): int|string
    {
        return $this->width;
    }

    public function setWidth(int|string $width): static
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    public function getMinimumLength(): ?int
    {
        return $this->minimumLength;
    }

    public function setMinimumLength(?int $minimumLength): void
    {
        $this->minimumLength = $minimumLength;
    }

    public function setAlgorithm(string $algorithm)
    {
        $this->algorithm = $algorithm;
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function setSalt(string $salt)
    {
        $this->salt = $salt;
    }

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function setSaltlocation(string $saltlocation)
    {
        $this->saltlocation = $saltlocation;
    }

    public function getSaltlocation(): string
    {
        return $this->saltlocation;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see ResourcePersistenceAwareInterface::getDataForResource
     *
     */
    public function getDataForResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        if (empty($data)) {
            return null;
        }

        // is already a hashed string? Then do not re-hash
        $info = password_get_info($data);
        if ($info['algo'] !== null && $info['algo'] !== 0) {
            return $data;
        }

        // password_get_info() will not detect older, less secure, hashing algos.
        // It might not detect some less common ones as well.
        $maybeHash = preg_match('/^[a-f0-9]{32,}$/i', $data);
        $hashLenghts = [
            32,  // MD2, MD4, MD5, RIPEMD-128, Snefru 128, Tiger/128, HAVAL128
            40,  // SHA-1, HAS-160, RIPEMD-160, Tiger/160, HAVAL160
            48,  // Tiger/192, HAVAL192
            56,  // SHA-224, HAVAL224
            64,  // SHA-256, BLAKE-256, GOST, GOST CryptoPro, HAVAL256, RIPEMD-256, Snefru 256
            96,  // SHA-384
            128, // SHA-512, BLAKE-512, SWIFFT
        ];

        if ($maybeHash && in_array(strlen($data), $hashLenghts, true)) {
            // Probably already a hashed string
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

        if (null !== $passwordModel && !$passwordModel instanceof DataObject\Classificationstore && !$passwordModel instanceof DataObject\Localizedfield) {
            $setter = 'set' . ucfirst($this->getName());
            $passwordModel->$setter($hashed);
        }

        return $hashed;
    }

    /**
     * Calculate hash according to configured parameters
     *
     * @param string $data
     *
     * @return string
     *@internal
     *
     */
    public function calculateHash(string $data): string
    {
        $hash = null;
        if ($this->algorithm === static::HASH_FUNCTION_PASSWORD_HASH) {
            $hash = password_hash($data, PASSWORD_DEFAULT);
        } else {
            if (!empty($this->salt)) {
                if ($this->saltlocation == 'back') {
                    $data = $data . $this->salt;
                } elseif ($this->saltlocation == 'front') {
                    $data = $this->salt . $data;
                }
            }

            $hash = hash($this->algorithm, $data);
        }

        return $hash;
    }

    /**
     * Verify password. Optionally re-hash the password if needed.
     *
     * Re-hash will be performed if PHP's password_hash default params (algorithm, cost) differ
     * from the ones which were used to create the hash (e.g. cost was increased from 10 to 12).
     * In this case, the hash will be re-calculated with the new parameters and saved back to the object.
     *
     * @param string $password
     * @param DataObject\Concrete $object
     * @param bool|true $updateHash
     *
     * @return bool
     *@internal
     *
     */
    public function verifyPassword(string $password, DataObject\Concrete $object, bool $updateHash = true): bool
    {
        $getter = 'get' . ucfirst($this->getName());
        $setter = 'set' . ucfirst($this->getName());

        $objectHash = $object->$getter();
        if (null === $objectHash || empty($objectHash)) {
            return false;
        }

        if ($this->getAlgorithm() === static::HASH_FUNCTION_PASSWORD_HASH) {
            $result = (true === password_verify($password, $objectHash));

            if ($result && $updateHash) {
                // password needs rehash (e.g PASSWORD_DEFAULT changed to a stronger algorithm)
                if (true === password_needs_rehash($objectHash, PASSWORD_DEFAULT)) {
                    $newHash = $this->calculateHash($password);

                    $object->$setter($newHash);
                    $object->save();
                }
            }
        } else {
            $hash = $this->calculateHash($password);
            $result = hash_equals($objectHash, $hash);
        }

        return $result;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see ResourcePersistenceAwareInterface::getDataFromResource
     *
     */
    public function getDataFromResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string|null
     *@see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     */
    public function getDataForQueryResource(mixed $data, DataObject\Concrete $object = null, array $params = []): ?string
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     * @see Data::getDataForEditmode
     *
     */
    public function getDataForEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     * @see Data::getDataFromEditmode
     *
     */
    public function getDataFromEditmode(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return $data;
    }

    /**
     * @param mixed $data
     * @param null|DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     * @see Data::getVersionPreview
     *
     */
    public function getVersionPreview(mixed $data, DataObject\Concrete $object = null, array $params = []): string
    {
        return '******';
    }

    public function getDataForGrid(string $data, Concrete $object, array $params = []): string
    {
        return '******';
    }

    public function getDataForSearchIndex(DataObject\Localizedfield|DataObject\Fieldcollection\Data\AbstractData|DataObject\Objectbrick\Data\AbstractData|DataObject\Concrete $object, array $params = []): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function isDiffChangeAllowed(Concrete $object, array $params = []): bool
    {
        return true;
    }

    /**
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode(array $data, $object = null, array $params = []): mixed
    {
        return $data[0]['data'];
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param array $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode(mixed $data, DataObject\Concrete $object = null, array $params = []): ?array
    {
        $diffdata = [];
        $diffdata['data'] = $data;
        $diffdata['disabled'] = !($this->isDiffChangeAllowed($object, $params));
        $diffdata['field'] = $this->getName();
        $diffdata['key'] = $this->getName();
        $diffdata['type'] = $this->fieldtype;

        if ($data) {
            $diffdata['value'] = $this->getVersionPreview($data, $object, $params);
            // $diffdata["value"] = $data;
        }

        $diffdata['title'] = !empty($this->title) ? $this->title : $this->name;

        $result = [];
        $result[] = $diffdata;

        return $result;
    }

    /**
     * @param DataObject\ClassDefinition\Data\Password $masterDefinition
     */
    public function synchronizeWithMasterDefinition(DataObject\ClassDefinition\Data $masterDefinition)
    {
        $this->algorithm = $masterDefinition->algorithm;
        $this->salt = $masterDefinition->salt;
        $this->saltlocation = $masterDefinition->saltlocation;
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
     * @param mixed $data
     * @param bool $omitMandatoryCheck
     * @param array $params
     *
     * @throws Model\Element\ValidationException|\Exception
     */
    public function checkValidity(mixed $data, bool $omitMandatoryCheck = false, array $params = [])
    {
        if (!$omitMandatoryCheck && ($this->getMinimumLength() && is_string($data) && strlen($data) < $this->getMinimumLength())) {
            throw new Model\Element\ValidationException('Value in field [ ' . $this->getName() . ' ] is not at least ' . $this->getMinimumLength() . ' characters');
        }

        parent::checkValidity($data, $omitMandatoryCheck, $params);
    }
}
