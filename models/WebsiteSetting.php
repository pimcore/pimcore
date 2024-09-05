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

namespace Pimcore\Model;

use Exception;
use Pimcore\Cache\RuntimeCache;
use Pimcore\Event\Model\WebsiteSettingEvent;
use Pimcore\Event\Traits\RecursionBlockingEventDispatchHelperTrait;
use Pimcore\Event\WebsiteSettingEvents;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Exception\NotFoundException;

/**
 * @method \Pimcore\Model\WebsiteSetting\Dao getDao()
 */
final class WebsiteSetting extends AbstractModel
{
    use RecursionBlockingEventDispatchHelperTrait;

    protected ?int $id = null;

    protected string $name = '';

    protected string $language = '';

    protected ?string $type = null;

    protected mixed $data = null;

    protected ?int $siteId = null;

    protected ?int $creationDate = null;

    protected ?int $modificationDate = null;

    /**
     * this is a small per request cache to know which website setting is which is, this info is used in self::getByName()
     *
     * @var array<string, int>
     */
    protected static array $nameIdMappingCache = [];

    protected static function getCacheKey(string $name, int $siteId = null, string $language = null): string
    {
        return $name . '~~~' . $siteId . '~~~' . $language;
    }

    public static function getById(int $id): ?WebsiteSetting
    {
        $cacheKey = 'website_setting_' . $id;

        if (RuntimeCache::isRegistered($cacheKey)) {
            return RuntimeCache::get($cacheKey);
        }

        $setting = new self();

        try {
            $setting->getDao()->getById($id);
        } catch (NotFoundException) {
            return null;
        }

        RuntimeCache::set($cacheKey, $setting);

        return $setting;
    }

    /**
     * @param string $name name of the config
     * @param int|null $siteId site ID
     * @param string|null $language language, if property cannot be found the value of property without language is returned
     * @param string|null $fallbackLanguage fallback language
     *
     * @throws Exception
     */
    public static function getByName(string $name, int $siteId = null, string $language = null, string $fallbackLanguage = null): ?WebsiteSetting
    {
        $nameCacheKey = static::getCacheKey($name, $siteId, $language);

        // check if pimcore already knows the id for this $name, if yes just return it
        if (array_key_exists($nameCacheKey, self::$nameIdMappingCache)) {
            return self::getById(self::$nameIdMappingCache[$nameCacheKey]);
        }

        // create a tmp object to obtain the id
        $setting = new self();

        try {
            $setting->getDao()->getByName($name, $siteId, $language);
        } catch (NotFoundException $e) {
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

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setCreationDate(int $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): ?int
    {
        return $this->creationDate;
    }

    /**
     * @return $this
     */
    public function setData(mixed $data): static
    {
        if ($data instanceof ElementInterface) {
            $this->setType(Service::getElementType($data));
            $data = $data->getId();
        }

        $this->data = $data;

        return $this;
    }

    public function getData(): mixed
    {
        // lazy-load data of type asset, document, object
        if (in_array($this->getType(), ['document', 'asset', 'object']) && !$this->data instanceof ElementInterface && is_numeric($this->data)) {
            return Element\Service::getElementById($this->getType(), (int) $this->data);
        }

        return $this->data;
    }

    /**
     * @return $this
     */
    public function setModificationDate(int $modificationDate): static
    {
        $this->modificationDate = $modificationDate;

        return $this;
    }

    public function getModificationDate(): ?int
    {
        return $this->modificationDate;
    }

    /**
     * @return $this
     */
    public function setSiteId(?int $siteId): static
    {
        $this->siteId = $siteId;

        return $this;
    }

    public function getSiteId(): ?int
    {
        return $this->siteId;
    }

    /**
     * enum('text','document','asset','object','bool')
     *
     *
     * @return $this
     */
    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * enum('text','document','asset','object','bool')
     *
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @internal
     */
    public function clearDependentCache(): void
    {
        \Pimcore\Cache::clearTag('website_config');
    }

    public function delete(): void
    {
        $nameCacheKey = self::getCacheKey($this->getName(), $this->getSiteId(), $this->getLanguage());

        // Remove cached element to avoid returning it with e.g. getByName() after if it is deleted
        if (array_key_exists($nameCacheKey, self::$nameIdMappingCache)) {
            unset(self::$nameIdMappingCache[$nameCacheKey]);
        }

        $event = new WebsiteSettingEvent($this);

        $this->dispatchEvent($event, WebsiteSettingEvents::PRE_DELETE);

        $this->getDao()->delete();

        $this->dispatchEvent($event, WebsiteSettingEvents::POST_DELETE);
    }

    public function save(): void
    {
        $event = new WebsiteSettingEvent($this);
        $isAdd = $this->id === null;

        $this->dispatchEvent($event, $isAdd ? WebsiteSettingEvents::PRE_ADD : WebsiteSettingEvents::PRE_UPDATE);

        $this->getDao()->save();

        $this->dispatchEvent($event, $isAdd ? WebsiteSettingEvents::POST_ADD : WebsiteSettingEvents::POST_UPDATE);
    }
}
