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
 * @package    Object|Class
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\Object;

class Password extends Model\Object\ClassDefinition\Data
{
    const HASH_FUNCTION_PASSWORD_HASH = 'password_hash';

    /**
     * Static type of this element
     *
     * @var string
     */
    public $fieldtype = "password";

    /**
     * @var integer
     */
    public $width;

    /**
     * Type for the column to query
     *
     * @var string
     */
    public $queryColumnType = "varchar(190)";

    /**
     * Type for the column
     *
     * @var string
     */
    public $columnType = "varchar(190)";

    /**
     * Type for the generated phpdoc
     *
     * @var string
     */
    public $phpdocType = "string";

    /**
     * @var string
     */
    public $algorithm = "md5";

    /**
     * @var string
     */
    public $salt = "";

    /**
     * @var string
     */
    public $saltlocation = "";

    /**
     * @return integer
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @param integer $width
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
     * @see Object\ClassDefinition\Data::getDataForResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        if (empty($data)) {
            return null;
        }

        if ($this->algorithm === static::HASH_FUNCTION_PASSWORD_HASH) {
            $info = password_get_info($data);

            // is already a hashed string
            if ($info['algo'] !== 0) {
                return $data;
            }
        } else {
            // is already a hashed string
            if (strlen($data) >= 32) {
                return $data;
            }
        }

        $hashed = $this->calculateHash($data);

        /** set the hashed password back to the object, to be sure that is not plain-text after the first save
         this is especially to aviod plaintext passwords in the search-index see: PIMCORE-1406 */

        // a model should be switched if the context parameter is used,
        // for example: field collections would use \Pimcore\Model\Object\Fieldcollection\Data\Dao
        $passwordModel = array_key_exists('context', $params)
            ? $params['context']
            : ($object ?: null);

        if (null !== $passwordModel) {
            $setter = "set" . ucfirst($this->getName());
            $passwordModel->$setter($hashed);
        }

        return $hashed;
    }

    /**
     * Calculate hash according to configured parameters
     *
     * @param $data
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
     * @param $password
     * @param Object\AbstractObject $object
     * @param bool|true $updateHash
     * @return bool
     */
    public function verifyPassword($password, Object\AbstractObject $object, $updateHash = true)
    {
        $getter = 'get' . ucfirst($this->getName());
        $setter = 'set' . ucfirst($this->getName());

        $objectHash = $object->$getter();
        if (null === $objectHash || empty($objectHash)) {
            return false;
        }

        $result = false;
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
            $hash   = $this->calculateHash($password);
            $result = $hash === $objectHash;
        }

        return $result;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataFromResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForQueryResource
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Object\ClassDefinition\Data::getDataForEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Model\Object\ClassDefinition\Data::getDataFromEditmode
     * @param string $data
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @see Object\ClassDefinition\Data::getVersionPreview
     * @param string $data
     * @param null|Object\AbstractObject $object
     * @param mixed $params
     * @return string
     */
    public function getVersionPreview($data, $object = null, $params = [])
    {
        return "******";
    }

    /**
     * @param $data
     * @param $object
     * @param array $params
     * @return string
     */
    public function getDataForGrid($data, $object, $params = [])
    {
        return "******";
    }

    /**
     * fills object field data values from CSV Import String
     * @abstract
     * @param string $importValue
     * @param null|Model\Object\AbstractObject $object
     * @param mixed $params
     * @return Object\ClassDefinition\Data
     */
    public function getFromCsvImport($importValue, $object = null, $params = [])
    {
        return $this->getDataFromEditmode($importValue, $object, $params);
    }

    /**
     * @param $object
     * @param mixed $params
     * @return string
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        return "";
    }

    /**
     * converts data to be exposed via webservices
     * @param string $object
     * @param mixed $params
     * @return mixed
     */
    public function getForWebserviceExport($object, $params = [])
    {
        //neither hash nor password is exported via WS
        return null;
    }

    /** True if change is allowed in edit mode.
     * @param string $object
     * @param mixed $params
     * @return bool
     */
    public function isDiffChangeAllowed($object, $params = [])
    {
        return true;
    }

    /** See parent class.
     * @param $data
     * @param null $object
     * @param mixed $params
     * @return null|\Pimcore_Date
     */

    public function getDiffDataFromEditmode($data, $object = null, $params = [])
    {
        return $data[0]["data"];
    }


    /** See parent class.
     * @param mixed $data
     * @param null $object
     * @param mixed $params
     * @return array|null
     */
    public function getDiffDataForEditMode($data, $object = null, $params = [])
    {
        $diffdata = [];
        $diffdata["data"] = $data;
        $diffdata["disabled"] = !($this->isDiffChangeAllowed($object, $params));
        $diffdata["field"] = $this->getName();
        $diffdata["key"] = $this->getName();
        $diffdata["type"] = $this->fieldtype;

        if ($data) {
            $diffdata["value"] = $this->getVersionPreview($data, $object, $params);
            // $diffdata["value"] = $data;
        }

        $diffdata["title"] = !empty($this->title) ? $this->title : $this->name;

        $result = [];
        $result[] = $diffdata;

        return $result;
    }

    /**
     * @param Object\ClassDefinition\Data $masterDefinition
     */
    public function synchronizeWithMasterDefinition(Object\ClassDefinition\Data $masterDefinition)
    {
        $this->algorithm = $masterDefinition->algorithm;
        $this->salt = $masterDefinition->salt;
        $this->saltlcoation = $masterDefinition->saltlcoation;
    }
}
