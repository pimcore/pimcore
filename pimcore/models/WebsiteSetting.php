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
 * @method \Pimcore\Model\WebsiteSetting\Dao getDao()
 * @method void save()
 * @method void delete()
 */
class WebsiteSetting extends AbstractModel
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $language;

    /**
     * @var
     */
    public $type;

    /**
     * @var
     */
    public $data;

    /**
     * @var
     */
    public $siteId;

    /**
     * @var
     */
    public $creationDate;

    /**
     * @var
     */
    public $modificationDate;

    /**
     * this is a small per request cache to know which website setting is which is, this info is used in self::getByName()
     *
     * @var array
     */
    protected static $nameIdMappingCache = [];

    /**
     * @param int $id
     *
     * @return WebsiteSetting
     */
    public static function getById($id)
    {
        $cacheKey = 'website_setting_' . $id;

        try {
            $setting = \Pimcore\Cache\Runtime::get($cacheKey);
            if (!$setting) {
                throw new \Exception('Website setting in registry is null');
            }
        } catch (\Exception $e) {
            $setting = new self();
            $setting->setId(intval($id));
            $setting->getDao()->getById();
            \Pimcore\Cache\Runtime::set($cacheKey, $setting);
        }

        return $setting;
    }

    /**
     * @param string $name name of the config
     * @param null $siteId site ID
     * @param null $language language, if property cannot be found the value of property without language is returned
     * @param null $fallbackLanguage fallback language
     *
     * @return null|WebsiteSetting
     */
    public static function getByName($name, $siteId = null, $language = null, $fallbackLanguage = null)
    {
        $nameCacheKey = $name . '~~~' . $siteId . '~~~' . $language;

        // check if pimcore already knows the id for this $name, if yes just return it
        if (array_key_exists($nameCacheKey, self::$nameIdMappingCache)) {
            return self::getById(self::$nameIdMappingCache[$nameCacheKey]);
        }

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getDao()->getByName($name, $siteId, $language);
        } catch (\Exception $e) {
            Logger::warning($e->getMessage());

            if ($language != $fallbackLanguage) {
                $result = self::getByName($name, $siteId, $fallbackLanguage, $fallbackLanguage);

                return $result;
            }

            return null;
        }

        // to have a singleton in a way. like all instances of Element\ElementInterface do also, like DataObject\AbstractObject
        if ($setting->getId() > 0) {
            // add it to the mini-per request cache
            self::$nameIdMappingCache[$nameCacheKey] = $setting->getId();

            return self::getById($setting->getId());
        }

        return $setting;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $creationDate
     *
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->creationDate = (int) $creationDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $modificationDate
     *
     * @return $this
     */
    public function setModificationDate($modificationDate)
    {
        $this->modificationDate = (int) $modificationDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getModificationDate()
    {
        return $this->modificationDate;
    }

    /**
     * @param $siteId
     *
     * @return $this
     */
    public function setSiteId($siteId)
    {
        $this->siteId = (int) $siteId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSiteId()
    {
        return (int) $this->siteId;
    }

    /**
     * @param $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function clearDependentCache()
    {
        \Pimcore\Cache::clearTag('website_config');
    }
}
