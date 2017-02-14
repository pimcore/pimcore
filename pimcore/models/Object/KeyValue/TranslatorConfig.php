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
 * @package    Object
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Object\KeyValue;

use Pimcore\Model;
use Pimcore\Logger;

/**
 * @method \Pimcore\Model\Object\KeyValue\TranslatorConfig\Dao getDao()
 */
class TranslatorConfig extends Model\AbstractModel
{

    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var
     */
    public $translator;

    /**
     * @param integer $id
     * @return Model\Object\KeyValue\TranslatorConfig
     */
    public static function getById($id)
    {
        try {
            $config = new self();
            $config->setId(intval($id));
            $config->getDao()->getById();

            return $config;
        } catch (\Exception $e) {
            Logger::warning($e);
        }
    }

    /**
     * @param $name
     */
    public static function getByName($name)
    {
        try {
            $config = new self();
            $config->setName($name);
            $config->getDao()->getByName();

            return $config;
        } catch (\Exception $e) {
            Logger::warning($e);
        }
    }

    /**
     * @return Model\Object\KeyValue\TranslatorConfig
     */
    public static function create()
    {
        $config = new self();
        $config->save();

        return $config;
    }

    /**
     * @param integer $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
