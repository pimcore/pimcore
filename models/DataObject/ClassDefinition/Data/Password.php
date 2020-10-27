<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;

class Password extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\ClassDefinition\NullablePhpdocReturnTypeTrait;

    const HASH_FUNCTION_PASSWORD_HASH = 'password_hash';

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = 'password';

    /**
     * @var int
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = 'varchar(255)';

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = 'varchar(255)';

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = 'string';

    /**
     * @var string
     */
    public $algorithm = self::HASH_FUNCTION_PASSWORD_HASH;

    /**
     * @var string
     */
    public $salt = '';

    /**
     * @var string
     */
    public $saltlocation = '';

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $this->getAsIntegerCast($width);

        return $this;
    }

    /**
     * @param string $algorithm
     */
    public function setAlgorithm($algorithm)
    {
        $this->algorithm = $algorithm;
    }

    /**
     * @return string
     */
    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * @param string $saltlocation
     */
    public function setSaltlocation($saltlocation)
    {
        $this->saltlocation = $saltlocation;
    }

    /**
     * @return string
     */
    public function getSaltlocation()
    {
        return $this->saltlocation;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
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

        if (null !== $passwordModel) {
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
     * @return bool|null|string
     */
    public function calculateHash($data)
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
     */
    public function verifyPassword($password, DataObject\Concrete $object, $updateHash = true)
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
            $result = $hash === $objectHash;
        }

        return $result;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Data::getVersionPreview
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return '******';
    }

    /**
     * @param string $data
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return string
     */
    public function getDataForGrid($data, $object, $params = [])
    {
        return '******';
    }

    /**
     * fills object field data values from CSV Import String
     *
     * @abstract
     *
     * @param string $importValue
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($importValue, $object, $params);
    }

    /**
     * @param DataObject\Concrete|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return '';
    }

    /**
     * converts data to be exposed via webservices
     *
     * @deprecated
     *
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return null
     */
    public function getForWebserviceExport($object, $params = [])
    {
        //neither hash nor password is exported via WS
        return null;
    }

    /** True if change is allowed in edit mode.
     * @param DataObject\Concrete $object
     * @param array $params
     *
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /**
     * @param array $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return mixed
     */
    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        return $data[0]['data'];
    }

    /** See parent class.
     * @param mixed $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
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
}
