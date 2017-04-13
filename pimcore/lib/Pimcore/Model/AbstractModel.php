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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model;

use Pimcore\Logger;

/**
 * @method void beginTransaction()
 * @method void commit()
 * @method void rollBack()
 * @method void configure()
 * @method array getValidTableColumns(string $table, bool $cache)
 * @method void resetValidTableColumnsCache(string $table)
 */
abstract class AbstractModel
{
    /**
     * @var \Pimcore\Model\Dao\AbstractDao
     */
    protected $dao;

    /**
     * @var array
     */
    private static $daoClassCache = [];

    /**
     * @return \Pimcore\Model\Dao\AbstractDao
     */
    public function getDao()
    {
        if (!$this->dao) {
            $this->initDao();
        }

        return $this->dao;
    }

    /**
     * @param $dao
     *
     * @return self
     */
    public function setDao($dao)
    {
        $this->dao = $dao;

        return $this;
    }

    /**
     * @deprecated
     *
     * @return Dao\AbstractDao
     */
    public function getResource()
    {
        return $this->getDao();
    }

    /**
     * @param null $key
     * @param bool $forceDetection
     *
     * @throws \Exception
     */
    public function initDao($key = null, $forceDetection = false)
    {
        $myClass = get_class($this);
        $cacheKey = $myClass . ($key ? ('-' . $key) : '');
        $dao = null;

        $forbiddenClassNames = ['Pimcore\\Resource'];

        if (!$forceDetection && array_key_exists($cacheKey, self::$daoClassCache)) {
            $dao = self::$daoClassCache[$cacheKey];
        } elseif (!$key || $forceDetection) {
            $myClass = $key ? $key : $myClass;
            $classes = class_parents($myClass);
            array_unshift($classes, $myClass);

            foreach ($classes as $class) {
                $delimiter = '_'; // old prefixed class style
                if (strpos($class, '\\')) {
                    $delimiter = '\\'; // that's the new with namespaces
                }

                $classParts = explode($delimiter, $class);
                $length = count($classParts);
                $className = null;

                for ($i = 0; $i < $length; $i++) {
                    $classNames = [
                        implode($delimiter, $classParts) . $delimiter . 'Dao',
                        implode($delimiter, $classParts) . $delimiter . 'Resource'
                    ];

                    foreach ($classNames as $tmpClassName) {
                        if (class_exists($tmpClassName) && !in_array($tmpClassName, $forbiddenClassNames)) {
                            $className = $tmpClassName;
                            break;
                        }
                    }

                    if ($className) {
                        break;
                    }

                    array_pop($classParts);
                }

                if ($className) {
                    $dao = $className;
                    self::$daoClassCache[$cacheKey] = $dao;

                    break;
                }
            }
        } elseif ($key) {
            $delimiter = '_'; // old prefixed class style
            if (strpos($key, '\\') !== false) {
                $delimiter = '\\'; // that's the new with namespaces
            }

            $dao = $key . $delimiter . 'Dao';

            self::$daoClassCache[$cacheKey] = $dao;
        }

        if (!$dao) {
            Logger::critical('No dao implementation found for: ' . $myClass);
            throw new \Exception('No dao implementation found for: ' . $myClass);
        }

        $dao = '\\' . ltrim($dao, '\\');

        $this->dao = new $dao();
        $this->dao->setModel($this);

        $this->dao->configure();

        if (method_exists($this->dao, 'init')) {
            $this->dao->init();
        }
    }

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setValues($data = [])
    {
        if (is_array($data) && count($data) > 0) {
            foreach ($data as $key => $value) {
                $this->setValue($key, $value);
            }
        }

        return $this;
    }

    /**
     * @param  $key
     * @param  $value
     *
     * @return $this
     */
    public function setValue($key, $value)
    {
        $method = 'set' . $key;
        if (method_exists($this, $method)) {
            $this->$method($value);
        } elseif (method_exists($this, 'set' . preg_replace('/^o_/', '', $key))) {
            // compatibility mode for objects (they do not have any set_oXyz() methods anymore)
            $this->$method($value);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        $finalVars = [];
        $blockedVars = ['dao', '_fulldump']; // _fulldump is a temp var which is used to trigger a full serialized dump in __sleep eg. in Document, \Object_Abstract
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * @param $method
     * @param $args
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function __call($method, $args)
    {

        // protected / private methods shouldn't be delegated to the dao -> this can have dangerous effects
        if (!is_callable([$this, $method])) {
            throw new \Exception("Unable to call private/protected method '" . $method . "' on object " . get_class($this));
        }

        // check if the method is defined in ´dao
        if (method_exists($this->getDao(), $method)) {
            try {
                $r = call_user_func_array([$this->getDao(), $method], $args);

                return $r;
            } catch (\Exception $e) {
                Logger::emergency($e);
                throw $e;
            }
        } else {
            Logger::error('Class: ' . get_class($this) . ' => call to undefined method ' . $method);
            throw new \Exception('Call to undefined method ' . $method . ' in class ' . get_class($this));
        }
    }

    public function __clone()
    {
        $this->dao = null;
    }

    /**
     * returns object values without the dao
     *
     * @return array
     */
    public function getObjectVars()
    {
        $data = get_object_vars($this);
        unset($data['dao']);

        return $data;
    }
}
