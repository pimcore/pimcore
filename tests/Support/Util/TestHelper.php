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

namespace Pimcore\Tests\Support\Util;

use DateTimeInterface;
use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Localization\LocaleServiceInterface;
use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject as ObjectModel;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Unittest;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\Property;
use Pimcore\Tests\Support\Helper\DataType\TestDataHelper;
use Pimcore\Tool;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Traversable;

class TestHelper
{
    public static array $thumbnail_configs = [];

    /**
     * Constant will be defined upon suite initialization and will result to true
     * if we have a valid DB configuration.
     *
     */
    public static function supportsDbTests(): bool
    {
        return defined('PIMCORE_TEST_DB_INITIALIZED') ? PIMCORE_TEST_DB_INITIALIZED : false;
    }

    /**
     * Check DB support and mark test skipped if DB connection wasn't established
     */
    public static function checkDbSupport(): void
    {
        if (!static::supportsDbTests()) {
            throw new \PHPUnit\Framework\SkippedTestError('Not running test as DB is not connected');
        }
    }

    /**
     * @param Property[] $properties
     *
     * @throws Exception
     */
    protected static function createPropertiesComparisonString(array $properties): array
    {
        $propertiesStringArray = [];

        ksort($properties);

        if (is_array($properties)) {
            foreach ($properties as $key => $value) {
                if (in_array($value->getType(), ['document', 'asset', 'object'])) {
                    if ($value->getData() instanceof ElementInterface) {
                        $propertiesStringArray['property_' . $key . '_' . $value->getType()] = 'property_' . $key . '_' . $value->getType() . ':' . $value->getData()->getId();
                    } else {
                        $propertiesStringArray['property_' . $key . '_' . $value->getType()] = 'property_' . $key . '_' . $value->getType() . ': null';
                    }
                } elseif ($value->getType() === 'date') {
                    if ($value->getData() instanceof DateTimeInterface) {
                        $propertiesStringArray['property_' . $key . '_' . $value->getType()] = 'property_' . $key . '_' . $value->getType() . ':' . $value->getData()->getTimestamp();
                    }
                } elseif ($value->getType() === 'bool') {
                    $propertiesStringArray['property_' . $key . '_' . $value->getType()] = 'property_' . $key . '_' . $value->getType() . ':' . (bool)$value->getData();
                } elseif (in_array($value->getType(), ['text', 'select'])) {
                    $propertiesStringArray['property_' . $key . '_' . $value->getType()] = 'property_' . $key . '_' . $value->getType() . ':' . $value->getData();
                } else {
                    throw new Exception('Unknown property of type [ ' . $value->getType() . ' ]');
                }
            }
        }

        return $propertiesStringArray;
    }

    /**
     * @throws Exception
     */
    public static function createAssetComparisonString(Asset $asset, bool $ignoreCopyDifferences = false): ?string
    {
        if ($asset instanceof Asset) {
            $a = [];

            // custom settings
            if (is_array($asset->getCustomSettings())) {
                $a['customSettings'] = serialize($asset->getCustomSettings());
            }

            if ($asset->getData()) {
                $a['data'] = base64_encode($asset->getData());
            }

            if (!$ignoreCopyDifferences) {
                $a['filename'] = $asset->getFilename();
                $a['id'] = $asset->getId();
                $a['modification'] = $asset->getModificationDate();
                $a['creation'] = $asset->getCreationDate();
                $a['userModified'] = $asset->getUserModification();
                $a['parentId'] = $asset->getParentId();
                $a['path'] = $asset->getPath();
            }

            $a['userOwner'] = $asset->getUserOwner();

            $properties = $asset->getProperties();

            $a = array_merge($a, self::createPropertiesComparisonString($properties));

            return implode(',', $a);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public static function assetsAreEqual(Asset $asset1, Asset $asset2, bool $ignoreCopyDifferences = false, bool $id = false): bool
    {
        if ($asset1 instanceof Asset and $asset2 instanceof Asset) {
            $a1Hash = self::createAssetComparisonString($asset1, $ignoreCopyDifferences);
            $a2Hash = self::createAssetComparisonString($asset2, $ignoreCopyDifferences);

            return $a1Hash === $a2Hash ? true : false;
        } else {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    public static function createDocumentComparisonString(Document $document, bool $ignoreCopyDifferences = false): ?string
    {
        if ($document instanceof Document) {
            $d = [];

            if ($document instanceof Document\PageSnippet) {
                $editables = $document->getEditables();

                ksort($editables);

                /** @var Document\Editable $value */
                foreach ($editables as $key => $value) {
                    if ($value instanceof Document\Editable\Video) {
                        // with video can't use frontend(), it includes random id
                        $d['editable_' . $key] = $value->getName() . ':' . $value->getType() . '_' . $value->getId();
                    } elseif (!$value instanceof Document\Editable\Block) {
                        $d['editable_' . $key] = $value->getName() . ':' . $value->frontend();
                    } else {
                        $d['editable_' . $key] = $value->getName();
                    }
                }

                if ($document instanceof Document\Page) {
                    $d['title'] = $document->getTitle();
                    $d['description'] = $document->getDescription();
                }

                $d['published'] = $document->isPublished();
            }

            if ($document instanceof Document\Link) {
                $d['link'] = $document->getHtml();
            }

            if (!$ignoreCopyDifferences) {
                $d['key'] = $document->getKey();
                $d['id'] = $document->getId();
                $d['modification'] = $document->getModificationDate();
                $d['creation'] = $document->getCreationDate();
                $d['userModified'] = $document->getUserModification();
                $d['parentId'] = $document->getParentId();
                $d['path'] = $document->getPath();
            }

            $d['userOwner'] = $document->getUserOwner();

            $properties = $document->getProperties();

            $d = array_merge($d, self::createPropertiesComparisonString($properties));

            return implode(',', $d);
        } else {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public static function documentsAreEqual(Document $doc1, Document $doc2, bool $ignoreCopyDifferences = false): bool
    {
        if ($doc1 instanceof Document and $doc2 instanceof Document) {
            $d1Hash = self::createDocumentComparisonString($doc1, $ignoreCopyDifferences);
            $d2Hash = self::createDocumentComparisonString($doc2, $ignoreCopyDifferences);

            return $d1Hash === $d2Hash ? true : false;
        } else {
            return false;
        }
    }

    public static function getComparisonDataForField(string $key, ObjectModel\ClassDefinition\Data $fd, AbstractObject $object): string|array|null
    {
        // omit password, this one we don't get through WS,
        // omit non owner objects, they don't get through WS,
        // plus omit fields which don't have get method
        $getter = 'get' . ucfirst($key);

        if (method_exists($object, $getter) && $fd instanceof ObjectModel\ClassDefinition\Data\Fieldcollections) {
            if ($object->$getter()) {
                /** @var ObjectModel\Fieldcollection $collection */
                $collection = $object->$getter();
                $items = $collection->getItems();

                if (is_array($items)) {
                    $returnValue = [];
                    $counter = 0;

                    /** @var ObjectModel\Fieldcollection\Data\AbstractData $item */
                    foreach ($items as $item) {
                        $def = $item->getDefinition();

                        foreach ($def->getFieldDefinitions() as $k => $v) {
                            $getter = 'get' . ucfirst($v->getName());
                            $fieldValue = $item->$getter();

                            if ($v instanceof ObjectModel\ClassDefinition\Data\Link) {
                                $fieldValue = serialize($v);
                            } elseif ($v instanceof ObjectModel\ClassDefinition\Data\Password || $fd instanceof ObjectModel\ClassDefinition\Data\ReverseObjectRelation) {
                                $fieldValue = null;
                            } else {
                                $fieldValue = $v->getForCsvExport($item);
                            }

                            $returnValue[$counter][$k] = $fieldValue;
                        }

                        $counter++;
                    }

                    return serialize($returnValue);
                }
            }
        } elseif (method_exists($object, $getter) && $fd instanceof ObjectModel\ClassDefinition\Data\Localizedfields) {
            $data = $object->$getter();
            $lData = [];

            if (!$data instanceof ObjectModel\Localizedfield) {
                return [];
            }

            $localeService = Pimcore::getContainer()->get(LocaleServiceInterface::class);
            $localeBackup = $localeService->getLocale();

            $validLanguages = Tool::getValidLanguages();

            foreach ($validLanguages as $language) {
                /** @var ObjectModel\ClassDefinition\Data $nestedFd */
                foreach ($fd->getFieldDefinitions() as $nestedFd) {
                    $localeService->setLocale($language);
                    $lData[$language][$nestedFd->getName()] = self::getComparisonDataForField($nestedFd->getName(), $nestedFd, $object);
                }
            }

            $localeService->setLocale($localeBackup);

            return serialize($lData);
        } elseif (method_exists($object, $getter) && $fd instanceof ObjectModel\Data\Link) {
            return serialize($fd);
        } elseif (method_exists($object, $getter) && !$fd instanceof ObjectModel\ClassDefinition\Data\Password && !$fd instanceof ObjectModel\ClassDefinition\Data\ReverseObjectRelation) {
            return $fd->getForCsvExport($object);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public static function createObjectComparisonString(AbstractObject $object, bool $ignoreCopyDifferences = false): ?string
    {
        if ($object instanceof AbstractObject) {
            $o = [];

            if ($object instanceof Concrete) {
                foreach ($object->getClass()->getFieldDefinitions() as $key => $value) {
                    $o[$key] = self::getComparisonDataForField($key, $value, $object);
                }

                $o['published'] = $object->isPublished();
            }
            if (!$ignoreCopyDifferences) {
                $o['id'] = $object->getId();
                $o['key'] = $object->getKey();
                $o['modification'] = $object->getModificationDate();
                $o['creation'] = $object->getCreationDate();
                $o['userModified'] = $object->getUserModification();
                $o['parentId'] = $object->getParentId();
                $o['path'] = $object->getPath();
            }

            $o['userOwner'] = $object->getUserOwner();

            $properties = $object->getProperties();

            $o = array_merge($o, self::createPropertiesComparisonString($properties));

            return implode(',', $o);
        } else {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public static function objectsAreEqual(AbstractObject $object1, AbstractObject $object2, bool $ignoreCopyDifferences = false): bool
    {
        if ($object1 instanceof AbstractObject and $object2 instanceof AbstractObject) {
            $o1Hash = self::createObjectComparisonString($object1, $ignoreCopyDifferences);
            $o2Hash = self::createObjectComparisonString($object2, $ignoreCopyDifferences);

            $id = uniqid();

            return $o1Hash === $o2Hash ? true : false;
        } else {
            return false;
        }
    }

    /**
     * @throws Exception
     */
    public static function createEmptyObject(string $keyPrefix = '', bool $save = true, bool $publish = true, ?string $type = null): Concrete
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        if (null === $type) {
            $type = Unittest::class;
        }

        /** @var Concrete $emptyObject */
        $emptyObject = new $type();
        $emptyObject->setOmitMandatoryCheck(true);
        $emptyObject->setParentId(1);
        $emptyObject->setUserOwner(1);
        $emptyObject->setUserModification(1);
        $emptyObject->setCreationDate(time());
        $emptyObject->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($publish) {
            $emptyObject->setPublished(true);
        }

        if ($save) {
            $emptyObject->save();
        }

        return $emptyObject;
    }

    /**
     * @throws ValidationException
     */
    public static function createObjectFolder(string $keyPrefix = '', bool $save = true): DataObject\Folder
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $folder = new ObjectModel\Folder();
        $folder->setParentId(1);
        $folder->setUserOwner(1);
        $folder->setUserModification(1);
        $folder->setCreationDate(time());
        $folder->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($save) {
            $folder->save();
        }

        return $folder;
    }

    /**
     * @return Unittest[]
     *
     * @throws Exception
     */
    public static function createEmptyObjects(string $keyPrefix = '', bool $save = true, int $count = 10): array
    {
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = self::createEmptyObject($keyPrefix, $save);
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public static function createFullyFledgedObject(TestDataHelper $testDataHelper, string $keyPrefix = '', bool $save = true, bool $publish = true, int $seed = 1): Unittest
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $object = new Unittest();
        $object->setOmitMandatoryCheck(true);
        $object->setParentId(1);
        $object->setUserOwner(1);
        $object->setUserModification(1);
        $object->setCreationDate(time());
        $object->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($publish) {
            $object->setPublished(true);
        }

        $testDataHelper->fillInput($object, 'input', $seed);
        $testDataHelper->fillNumber($object, 'number', $seed);
        $testDataHelper->fillTextarea($object, 'textarea', $seed);
        $testDataHelper->fillSlider($object, 'slider', $seed);
        $testDataHelper->fillHref($object, 'href', $seed);
        $testDataHelper->fillMultihref($object, 'multihref', $seed);
        $testDataHelper->fillImage($object, 'image', $seed);
        $testDataHelper->fillHotspotImage($object, 'hotspotimage', $seed);
        $testDataHelper->fillLanguage($object, 'languagex', $seed);
        $testDataHelper->fillCountry($object, 'country', $seed);
        $testDataHelper->fillDate($object, 'date', $seed);
        $testDataHelper->fillDate($object, 'datetime', $seed);
        $testDataHelper->fillTime($object, 'time', $seed);
        $testDataHelper->fillSelect($object, 'select', $seed);
        $testDataHelper->fillMultiSelect($object, 'multiselect', $seed);
        $testDataHelper->fillUser($object, 'user', $seed);
        $testDataHelper->fillCheckbox($object, 'checkbox', $seed);
        $testDataHelper->fillBooleanSelect($object, 'booleanSelect', $seed);
        $testDataHelper->fillWysiwyg($object, 'wysiwyg', $seed);
        $testDataHelper->fillPassword($object, 'password', $seed);
        $testDataHelper->fillMultiSelect($object, 'countries', $seed);
        $testDataHelper->fillMultiSelect($object, 'languages', $seed);
        $testDataHelper->fillGeoCoordinates($object, 'point', $seed);
        $testDataHelper->fillGeobounds($object, 'bounds', $seed);
        $testDataHelper->fillGeopolygon($object, 'poly', $seed);
        $testDataHelper->fillTable($object, 'table', $seed);
        $testDataHelper->fillLink($object, 'link', $seed);
        $testDataHelper->fillStructuredTable($object, 'structuredtable', $seed);
        $testDataHelper->fillObjects($object, 'objects', $seed);
        $testDataHelper->fillObjectsWithMetadata($object, 'objectswithmetadata', $seed);

        $testDataHelper->fillInput($object, 'linput', $seed, 'de');
        $testDataHelper->fillInput($object, 'linput', $seed, 'en');

        $testDataHelper->fillObjects($object, 'lobjects', $seed, 'de');
        $testDataHelper->fillObjects($object, 'lobjects', $seed, 'en');

        $testDataHelper->fillBricks($object, 'mybricks', $seed);
        $testDataHelper->fillFieldCollection($object, 'myfieldcollection', $seed);

        if ($save) {
            $object->save();
        }

        return $object;
    }

    public static function createEmptyDocument(?string $keyPrefix = '', bool $save = true, bool $publish = true, string $type = '\\Pimcore\\Model\\Document\\Page'): Document
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $document = new $type();
        $document->setParentId(1);
        $document->setUserOwner(1);
        $document->setUserModification(1);
        $document->setCreationDate(time());
        $document->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($publish) {
            $document->setPublished(true);
        }

        if ($save) {
            $document->save();
        }

        return $document;
    }

    public static function createEmptyDocumentPage(?string $keyPrefix = '', bool $save = true, bool $publish = true): Document\Page
    {
        return self::createEmptyDocument($keyPrefix, $save, $publish);
    }

    /**
     * @throws Exception
     */
    public static function createDocumentFolder(?string $keyPrefix = '', bool $save = true): Document\Folder
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $folder = new Document\Folder();
        $folder->setParentId(1);
        $folder->setUserOwner(1);
        $folder->setUserModification(1);
        $folder->setCreationDate(time());
        $folder->setKey($keyPrefix . uniqid() . rand(10, 99));

        if ($save) {
            $folder->save();
        }

        return $folder;
    }

    /**
     * @throws Exception
     */
    public static function createImageAsset(string $keyPrefix = '', ?string $data = null, bool $save = true, string $filePath = 'assets/images/image5.jpg'): Asset\Image
    {
        if (!$data) {
            $path = static::resolveFilePath($filePath);
            if (!file_exists($path)) {
                throw new RuntimeException(sprintf('Path %s was not found', $path));
            }

            $data = file_get_contents($path);
        }

        $asset = new Asset\Image();
        $asset->setParentId(1);
        $asset->setUserOwner(1);
        $asset->setUserModification(1);
        $asset->setCreationDate(time());
        $asset->setData($data);
        $asset->setType('image');

        $property = new Property();
        $property->setName('propname');
        $property->setType('text');
        $property->setData('bla');

        $properties = [$property];
        $asset->setProperties($properties);

        $asset->setFilename($keyPrefix . uniqid() . rand(10, 99) . '.jpg');

        if ($save) {
            $asset->save();
        }

        return $asset;
    }

    /**
     * @throws Exception
     */
    public static function createDocumentAsset(string $keyPrefix = '', ?string $data = null, bool $save = true): Asset\Document
    {
        if (!$data) {
            $path = static::resolveFilePath('assets/document/sonnenblume.pdf');
            if (!file_exists($path)) {
                throw new RuntimeException(sprintf('Path %s was not found', $path));
            }

            $data = file_get_contents($path);
        }

        $asset = new Asset\Document();
        $asset->setParentId(1);
        $asset->setUserOwner(1);
        $asset->setUserModification(1);
        $asset->setCreationDate(time());
        $asset->setData($data);
        $asset->setType('document');

        $property = new Property();
        $property->setName('propname');
        $property->setType('text');
        $property->setData('bla');

        $properties = [$property];
        $asset->setProperties($properties);

        $asset->setFilename($keyPrefix . uniqid() . rand(10, 99) . '.pdf');

        if ($save) {
            $asset->save();
        }

        return $asset;
    }

    /**
     * @throws Exception
     */
    public static function createVideoAsset(string $keyPrefix = '', ?string $data = null, bool $save = true): Asset\Video
    {
        if (!$data) {
            $path = static::resolveFilePath('assets/video/example.mp4');
            if (!file_exists($path)) {
                throw new RuntimeException(sprintf('Path %s was not found', $path));
            }

            $data = file_get_contents($path);
        }

        $asset = new Asset\Video();
        $asset->setParentId(1);
        $asset->setUserOwner(1);
        $asset->setUserModification(1);
        $asset->setCreationDate(time());
        $asset->setData($data);
        $asset->setType('video');

        $property = new Property();
        $property->setName('propname');
        $property->setType('text');
        $property->setData('bla');

        $properties = [$property];
        $asset->setProperties($properties);

        $asset->setFilename($keyPrefix . uniqid() . rand(10, 99) . '.mp4');

        if ($save) {
            $asset->save();
        }

        return $asset;
    }

    /**
     * @throws Exception
     */
    public static function createAssetFolder(string $keyPrefix = '', bool $save = true): Asset\Folder
    {
        if (null === $keyPrefix) {
            $keyPrefix = '';
        }

        $folder = new Asset\Folder();
        $folder->setParentId(1);
        $folder->setUserOwner(1);
        $folder->setUserModification(1);
        $folder->setCreationDate(time());
        $folder->setFilename($keyPrefix . uniqid() . rand(10, 99));

        if ($save) {
            $folder->save();
        }

        return $folder;
    }

    public static function createTag(string $name, int $parentId = 0, bool $save = true): Tag
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->setParentId($parentId);

        if ($save) {
            $tag->save();
        }

        return $tag;
    }

    public static function assignTag(Tag $tag, ElementInterface $element): void
    {
        Tag::addTagToElement(match (true) {
            $element instanceof Asset => 'asset',
            $element instanceof Document => 'document',
            $element instanceof DataObject => 'object',
        }, $element->getId(), $tag);
    }

    /**
     * Clean up directory, deleting files one by one
     */
    public static function cleanupDirectory(string|Traversable|Finder $directory): void
    {
        $files = null;
        if ($directory instanceof Traversable) {
            $files = $directory;
        } else {
            $files = new Finder();
            $files
                ->files()
                ->in($directory)
                ->ignoreDotFiles(true);
        }

        $filesystem = new Filesystem();
        $filesystem->remove($files);
    }

    public static function cleanUp(
        bool $cleanObjects = true,
        bool $cleanDocuments = true,
        bool $cleanAssets = true,
        bool $cleanTags = true
    ): void {
        Pimcore::collectGarbage();

        if (!static::supportsDbTests()) {
            return;
        }

        try {
            if ($cleanObjects) {
                static::cleanUpTree(DataObject::getById(1), 'object');
                codecept_debug(sprintf('Number of objects is: %d', static::getObjectCount()));
            }

            if ($cleanAssets) {
                static::cleanUpTree(Asset::getById(1), 'asset');
                codecept_debug(sprintf('Number of assets is: %d', static::getAssetCount()));
            }

            if ($cleanDocuments) {
                static::cleanUpTree(Document::getById(1), 'document');
                codecept_debug(sprintf('Number of documents is: %d', static::getDocumentCount()));
            }

            if ($cleanTags) {
                static::cleanUpTags();
            }
        } catch (Exception $e) {
            Logger::error((string) $e);
        }

        Pimcore::collectGarbage();
    }

    /**
     * @throws Exception
     */
    public static function cleanUpTree(?ElementInterface $root, string $type): void
    {
        if (!($root instanceof AbstractObject || $root instanceof Document || $root instanceof Asset)) {
            throw new InvalidArgumentException(sprintf('Cleanup root type for %s needs to be one of: AbstractObject, Document, Asset', $type));
        }

        if ($root instanceof AbstractObject) {
            $children = $root->getChildren([], true);
        } elseif ($root instanceof Document) {
            $children = $root->getChildren(true);
        } else {
            $children = $root->getChildren();
        }

        /** @var ElementInterface $child */
        foreach ($children as $child) {
            codecept_debug(sprintf('Deleting %s %s (%d)', $type, $child->getFullPath(), $child->getId()));
            $child->delete();
        }
    }

    public static function cleanUpTags(): void
    {
        foreach ((new Tag\Listing()) as $tag) {
            codecept_debug(sprintf('Deleting tag %s (%d)', $tag->getNamePath(true), $tag->getId()));
            $tag->delete();
        }
    }

    /**
     * Returns the total number of objects.
     */
    public static function getObjectCount(): int
    {
        $list = new ObjectModel\Listing();
        $children = $list->load();

        return count($children);
    }

    /**
     * Returns the total number of assets.
     */
    public static function getAssetCount(): int
    {
        $list = new Asset\Listing();
        $children = $list->getAssets();

        return count($children);
    }

    /**
     * Returns the total number of documents.
     */
    public static function getDocumentCount(): int
    {
        $list = new Document\Listing();
        $children = $list->load();

        return count($children);
    }

    /**
     * Resolve path to resource path
     */
    public static function resolveFilePath(string $path): string
    {
        $path = __DIR__ . '/../Resources/' . ltrim($path, '/');

        return $path;
    }

    public static function generateRandomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }

    public static function clearThumbnailConfiguration(string $name): void
    {
        $pipe = Asset\Image\Thumbnail\Config::getByName($name);
        if ($pipe) {
            $pipe->delete(true);
        }
    }

    public static function clearThumbnailConfigurations(): void
    {
        foreach (self::$thumbnail_configs as $name) {
            static::clearThumbnailConfiguration($name);
        }
    }

    /**
     * @throws Exception
     */
    public static function createThumbnailConfigurationRotate(int $angle = 90): Asset\Image\Thumbnail\Config
    {
        $name = 'assettest_rotate_' . $angle;
        $pipe = Asset\Image\Thumbnail\Config::getByName($name);
        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config();
            $pipe->setName($name);
            $pipe->addItem('rotate', ['angle' => $angle], 'default');
            $pipe->save(true);
            self::$thumbnail_configs[] = $name;
        }

        return $pipe;
    }

    /**
     *
     * @throws Exception
     */
    public static function createThumbnailConfigurationScaleByWidth(int $width = 256, bool $forceResize = false): Asset\Image\Thumbnail\Config
    {
        $name = 'assettest_scaleByWidth_' . $width . '_' . $forceResize;
        $pipe = Asset\Image\Thumbnail\Config::getByName($name);
        if (!$pipe) {
            $pipe = new Asset\Image\Thumbnail\Config($name);
            $pipe->setName($name);
            $pipe->addItem('scaleByWidth', ['width' => $width, 'forceResize' => $forceResize], 'default');
            $pipe->save(true);
            self::$thumbnail_configs[] = $name;
        }

        return $pipe;
    }

    /**
     * This function allows to call private and protected methods
     *
     * @throws ReflectionException
     */
    public static function callMethod(object|string $obj, string $name, array $args): mixed
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);

        return $method->invokeArgs($obj, $args);
    }
}
