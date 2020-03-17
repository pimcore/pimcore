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
 * @package    Element
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Element\Import;

use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\Element;
use Pimcore\Model\Webservice;
use Pimcore\Tool;

/**
 * @deprecated
 */
class Service
{
    /**
     * @var Webservice\Service
     */
    protected $webService;

    /**
     * @var array
     */
    protected $importInfo;

    /**
     * @var Model\User
     */
    protected $user;

    /**
     * @param Model\User $user
     */
    public function __construct($user)
    {
        $this->webService = new Webservice\Service();
        $this->importInfo = [];
        $this->user = $user;
    }

    /**
     * @return Webservice\Service
     */
    public function getWebservice()
    {
        return $this->webService;
    }

    /**
     * @return array
     */
    public function getImportInfo()
    {
        return $this->importInfo;
    }

    /**
     * @throws \Exception
     *
     * @param Element\ElementInterface $rootElement
     * @param string $apiKey
     * @param string $path
     * @param Webservice\Data\Document|Webservice\Data\Asset\Folder|Webservice\Data\Asset\File|Webservice\Data\DataObject\Concrete|Webservice\Data\DataObject\Folder $apiElement
     * @param bool $overwrite
     * @param string $elementCounter
     *
     * @return Element\ElementInterface
     */
    public function create($rootElement, $apiKey, $path, $apiElement, $overwrite, $elementCounter)
    {

        //correct relative path
        if (strpos($path, '/') !== 0) {
            $path = $rootElement->getRealFullPath() . '/' . $path;
        }

        $type = $apiElement->type;

        if ($apiElement instanceof Webservice\Data\Asset) {
            $className = '\\Pimcore\\Model\\Asset\\' . ucfirst($type);
            $parentClassName = '\\Pimcore\\Model\\Asset';
            $maintype = 'asset';
            $fullPath = $path . $apiElement->filename;
        } elseif ($apiElement instanceof Webservice\Data\DataObject) {
            $maintype = 'object';
            if ($type == 'object') {
                $className = '\\Pimcore\\Model\\DataObject\\' . ucfirst($apiElement->className);
                if (!Tool::classExists($className)) {
                    throw new \Exception('Unknown class [ ' . $className . ' ]');
                }
            } else {
                $className = '\\Pimcore\\Model\\DataObject\\' . ucfirst($type);
            }
            $parentClassName = '\\Pimcore\\Model\\DataObject';
            $fullPath = $path . $apiElement->key;
        } elseif ($apiElement instanceof Webservice\Data\Document) {
            $maintype = 'document';
            $className = '\\Pimcore\\Model\\Document\\' . ucfirst($type);
            $parentClassName = '\\Pimcore\\Model\\Document';
            $fullPath = $path . $apiElement->key;
        } else {
            throw new \Exception('Unknown import element');
        }

        $existingElement = $className::getByPath($fullPath);
        if ($overwrite && $existingElement) {
            $apiElement->parentId = $existingElement->getParentId();

            return $existingElement;
        }

        /** @var Asset|Model\Document|DataObject\AbstractObject $element */
        $element = new $className();
        $element->setId(null);
        $element->setCreationDate(time());
        if ($element instanceof Asset) {
            $element->setFilename($apiElement->filename);
            $element->setData(base64_decode($apiElement->data));
        } elseif ($element instanceof DataObject\Concrete) {
            $element->setKey($apiElement->key);
            $element->setClassName($apiElement->className);
            $class = DataObject\ClassDefinition::getByName($apiElement->className);
            if (!$class instanceof DataObject\ClassDefinition) {
                throw new \Exception('Unknown object class [ ' . $apiElement->className . ' ] ');
            }
            $element->setClassId($class->getId());
        } else {
            $element->setKey($apiElement->key);
        }

        $this->setModificationParams($element, true);
        $key = $element->getKey();
        if (empty($key) and $apiElement->id == 1) {
            if ($element instanceof Asset) {
                $element->setFilename('home_' . uniqid());
            } else {
                $element->setKey('home_' . uniqid());
            }
        } elseif (empty($key)) {
            throw new \Exception('Cannot create element without key ');
        }

        $parent = $parentClassName::getByPath($path);

        if (Element\Service::getType($rootElement) == $maintype and $parent) {
            $element->setParentId($parent->getId());
            $apiElement->parentId = $parent->getId();
            $existingElement = $parentClassName::getByPath($parent->getRealFullPath() . '/' . $element->getKey());
            if ($existingElement) {
                //set dummy key to avoid duplicate paths
                if ($element instanceof Asset) {
                    $element->setFilename(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getFilename());
                } else {
                    $element->setKey(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getKey());
                }
            }
        } elseif (Element\Service::getType($rootElement) != $maintype) {
            //this is a related element - try to import it to it's original path or set the parent to home folder
            $potentialParent = $parentClassName::getByPath($path);

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getFilename());
            } else {
                $element->setKey(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getKey());
            }

            if ($potentialParent) {
                $element->setParentId($potentialParent->getId());
                //set actual id and path for second run
                $apiElements[$apiKey]['path'] = $potentialParent->getRealFullPath();
                $apiElement->parentId = $potentialParent->getId();
            } else {
                $element->setParentId(1);
                //set actual id and path for second run
                $apiElements[$apiKey]['path'] = '/';
                $apiElement->parentId = 1;
            }
        } else {
            $element->setParentId($rootElement->getId());
            //set actual id and path for second run
            $apiElements[$apiKey]['path'] = $rootElement->getRealFullPath();
            $apiElement->parentId = $rootElement->getId();

            //set dummy key to avoid duplicate paths
            if ($element instanceof Asset) {
                $element->setFilename(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getFilename());
            } else {
                $element->setKey(str_replace('/', '_', $apiElement->path) . uniqid() . '_' . $elementCounter . '_' . $element->getKey());
            }
        }

        //if element exists, make temp key permanent by setting it in apiElement
        if ($parentClassName::getByPath($fullPath)) {
            if ($element instanceof Asset) {
                $apiElement->filename = $element->getFilename();
            } else {
                $apiElement->key = $element->getKey();
            }
        }

        $element->save();

        //TODO save type and id for later rollback
        $this->importInfo[Element\Service::getType($element) . '_' . $element->getId()] = ['id' => $element->getId(), 'type' => Element\Service::getType($element), 'fullpath' => $element->getRealFullPath()];

        return $element;
    }

    /**
     * @param Webservice\Data\Document|Webservice\Data\Asset|Webservice\Data\DataObject $apiElement
     * @param string $type
     * @param array $idMapping
     */
    public function correctElementIdRelations($apiElement, $type, $idMapping)
    {

        //correct id
        $apiElement->id = $idMapping[$type][$apiElement->id];

        //correct properties
        if ($apiElement->properties) {
            foreach ($apiElement->properties as $property) {
                if (in_array($property->type, ['asset', 'object', 'document'])) {
                    $property->data = $idMapping[$property->type][$property->data];
                }
            }
        }
    }

    /**
     * @param  Webservice\Data\Document\PageSnippet $apiElement
     * @param  array $idMapping
     */
    public function correctDocumentRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {
                if ($el->type == 'href' and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } elseif ($el->type == 'image' and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping['asset'][$el->value->id];
                } elseif ($el->type == 'wysiwyg' and is_object($el->value) and $el->value->text) {
                    $el->value->text = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $el->value->text);
                } elseif ($el->type == 'link' and is_object($el->value) and is_array($el->value->data) and $el->value->data['internalId']) {
                    $el->value->data['internalId'] = $idMapping[$el->value->data['internalType']][$el->value->data['internalId']];
                } elseif ($el->type == 'video' and is_object($el->value) and $el->value->type == 'asset') {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                } elseif ($el->type == 'snippet' and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping['document'][$el->value->id];
                } elseif ($el->type == 'renderlet' and is_object($el->value) and $el->value->id) {
                    $el->value->id = $idMapping[$el->value->type][$el->value->id];
                }
            }
        }
    }

    /**
     * @param Webservice\Data\DataObject\Concrete $apiElement
     * @param array $idMapping
     */
    public function correctObjectRelations($apiElement, $idMapping)
    {
        if ($apiElement->elements) {
            foreach ($apiElement->elements as $el) {
                if ($el->type == 'href' and $el->value['id']) {
                    $el->value['id'] = $idMapping[$el->value['type']][$el->value['id']];
                } elseif ($el->type == 'image' and $el->value) {
                    $el->value = $idMapping['asset'][$el->value];
                } elseif ($el->type == 'link' and $el->value['internal']) {
                    $el->value['internal'] = $idMapping[$el->value['internalType']][$el->value['internal']];
                } elseif ($el->type == 'multihref' || $el->type == 'manyToManyRelation') {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]['id'] = $idMapping[$el->value[$i]['type']][$el->value[$i]['id']];
                        }
                    }
                } elseif ($el->type == 'objects' || $el->type == 'manyToManyObjectRelation') {
                    if (is_array($el->value)) {
                        for ($i = 0; $i < count($el->value); $i++) {
                            $el->value[$i]['id'] = $idMapping['object'][$el->value[$i]['id']];
                        }
                    }
                } elseif ($el->type == 'wysiwyg') {
                    $el->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $el->value);
                } elseif ($el->type == 'fieldcollections') {
                    if ($el instanceof Webservice\Data\DataObject\Element and is_array($el->value)) {
                        foreach ($el->value as $fieldCollectionEl) {
                            if (is_array($fieldCollectionEl->value)) {
                                foreach ($fieldCollectionEl->value as $collectionItem) {
                                    if ($collectionItem->type == 'image') {
                                        $collectionItem->value = $idMapping['asset'][$collectionItem->value];
                                    } elseif ($collectionItem->type == 'wysiwyg') {
                                        $collectionItem->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $collectionItem->value);
                                    } elseif ($collectionItem->type == 'link' && $collectionItem->value['internalType']) {
                                        $collectionItem->value['internal'] = $idMapping[$collectionItem->value['internalType']][$collectionItem->value['internal']];
                                    } elseif ($collectionItem->type == 'href' && $collectionItem->value['id']) {
                                        $collectionItem->value['id'] = $idMapping[$collectionItem->value['type']][$collectionItem->value['id']];
                                    } elseif (($collectionItem->type == 'objects' || $collectionItem->type == 'multihref' || $collectionItem->type == 'manyToManyRelation' || $collectionItem->type == 'manyToManyObjectRelation') && is_array($collectionItem->value) && count($collectionItem->value) > 0) {
                                        for ($i = 0; $i < count($collectionItem->value); $i++) {
                                            if ($collectionItem->value[$i]['id']) {
                                                $collectionItem->value[$i]['id'] = $idMapping[$collectionItem->value[$i]['type']][$collectionItem->value[$i]['id']];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                } elseif ($el->type == 'localizedfields') {
                    if (is_array($el->value)) {
                        foreach ($el->value as $localizedDataEl) {
                            if ($localizedDataEl->type == 'image') {
                                $localizedDataEl->value = $idMapping['asset'][$localizedDataEl->value];
                            } elseif ($localizedDataEl->type == 'wysiwyg') {
                                $localizedDataEl->value = Tool\Text::replaceWysiwygTextRelationIds($idMapping, $localizedDataEl->value);
                            } elseif ($localizedDataEl->type == 'link' and $localizedDataEl->value['internalType']) {
                                $localizedDataEl->value['internal'] = $idMapping[$localizedDataEl->value['internalType']][$localizedDataEl->value['internal']];
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param Element\ElementInterface $element
     * @param bool $creation
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setModificationParams($element, $creation = false)
    {
        $user = $this->user;
        if (!$user instanceof Model\User) {
            throw new \Exception('No user present');
        }
        if ($creation) {
            $element->setUserOwner($user->getId());
        }
        $element->setUserModification($user->getId());
        $element->setModificationDate(time());

        return $this;
    }
}
