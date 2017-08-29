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
 * @package    Webservice
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Webservice;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\DataObject;
use Pimcore\Model\User;
use Pimcore\Model\Webservice;

class Service
{
    /**
     * @return User
     *
     * @throws \Exception
     */
    public function getUser()
    {
        if ($user = \Pimcore\Tool\Admin::getCurrentUser()) {
            return $user;
        }

        throw new \Exception('Webservice instantiated, but no user present');
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentFolderById($id)
    {
        try {
            $folder = Document::getById($id);
            if ($folder instanceof Document\Folder) {
                $className = Webservice\Data\Mapper::findWebserviceClass($folder, 'out');
                $apiFolder = Webservice\Data\Mapper::map($folder, $className, 'out');

                return $apiFolder;
            }

            throw new \Exception('Document Folder with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentLinkById($id)
    {
        try {
            $link = Document::getById($id);
            if ($link instanceof Document\Link) {
                $className = Webservice\Data\Mapper::findWebserviceClass($link, 'out');
                $apiLink = Webservice\Data\Mapper::map($link, $className, 'out');

                return $apiLink;
            }

            throw new \Exception('Document Link with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentHardLinkById($id)
    {
        try {
            $link = Document::getById($id);
            if ($link instanceof Document\Hardlink) {
                $className = Webservice\Data\Mapper::findWebserviceClass($link, 'out');
                $apiLink = Webservice\Data\Mapper::map($link, $className, 'out');

                return $apiLink;
            }

            throw new \Exception('Document Hardlink with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentEmailById($id)
    {
        try {
            $link = Document::getById($id);
            if ($link instanceof Document\Email) {
                $className = Webservice\Data\Mapper::findWebserviceClass($link, 'out');
                $apiLink = Webservice\Data\Mapper::map($link, $className, 'out');

                return $apiLink;
            }

            throw new \Exception('Document Email with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentPageById($id)
    {
        try {
            $page = Document::getById($id);
            if ($page instanceof Document\Page) {
                // load all data (eg. href, snippet, ... which are lazy loaded)
                Document\Service::loadAllDocumentFields($page);
                $className = Webservice\Data\Mapper::findWebserviceClass($page, 'out');
                $apiPage = Webservice\Data\Mapper::map($page, $className, 'out');

                return $apiPage;
            }

            throw new \Exception('Document Page with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getDocumentSnippetById($id)
    {
        try {
            $snippet = Document::getById($id);
            if ($snippet instanceof Document\Snippet) {
                // load all data (eg. href, snippet, ... which are lazy loaded)
                Document\Service::loadAllDocumentFields($snippet);
                $className = Webservice\Data\Mapper::findWebserviceClass($snippet, 'out');
                $apiSnippet = Webservice\Data\Mapper::map($snippet, $className, 'out');

                return $apiSnippet;
            }

            throw new \Exception('Document Snippet with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     *
     * @throws \Exception
     */
    public function getDocumentList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null)
    {
        try {
            $list = Document::getList([
                'condition' => $condition,
                'order' => $order,
                'orderKey' => $orderKey,
                'offset' => $offset,
                'limit' => $limit,
                'groupBy' => $groupBy
            ]);
            $list->setUnpublished(1);

            $items = [];
            /** @var $doc Document */
            foreach ($list as $doc) {
                $item = new Webservice\Data\Document\Listing\Item();
                $item->id = $doc->getId();
                $item->type = $doc->getType();
                if (method_exists($doc, 'getPublished')) {
                    $item->published = $doc->getPublished();
                }

                $items[] = $item;
            }

            return $items;
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function unpublishDocument($id)
    {
        try {
            $doc = Document::getById($id);
            if ($doc instanceof Document) {
                $doc->setPublished(false);
                $doc->save();

                return true;
            }

            throw new \Exception('Document with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function deleteDocument($id)
    {
        try {
            $doc = Document::getById($id);
            if ($doc instanceof Document) {
                $doc->delete();

                return true;
            }

            throw new \Exception('Document with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentPage($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Page\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Page. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Folder\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Folder. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentSnippet($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Snippet\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Snippet. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentLink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Link\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Link. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentHardlink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Hardlink\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Hardlink. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateDocumentEmail($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Email\In) {
                return $this->updateDocument($wsDocument);
            } else {
                throw new \Exception('Unable to update Document Email. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateObjectFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\DataObject\Folder\In) {
                return $this->updateObject($wsDocument);
            } else {
                throw new \Exception('Unable to update Object Folder. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateObjectConcrete($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\DataObject\Concrete\In) {
                return $this->updateObject($wsDocument);
            } else {
                throw new \Exception('Unable to update Object Concrete. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateAssetFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Asset\Folder\In) {
                return $this->updateAsset($wsDocument);
            } else {
                throw new \Exception('Unable to update Asset Folder. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function updateAssetFile($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Asset\File\In) {
                return $this->updateAsset($wsDocument);
            } else {
                throw new \Exception('Unable to update Asset Folder. Inappropriate Data given');
            }
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentPage($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Page\In) {
                $wsDocument->type = 'page';
                $document = new Document\Page();

                return $this->create($wsDocument, $document);
            }
            throw new \Exception('Unable to create new Document Page.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentSnippet($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Snippet\In) {
                $wsDocument->type = 'snippet';
                $document = new Document\Snippet();

                return $this->create($wsDocument, $document);
            }

            throw new \Exception('Unable to create new Document Snippet.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentEmail($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Email\In) {
                $wsDocument->type = 'email';
                $document = new Document\Email();

                return $this->create($wsDocument, $document);
            }

            throw new \Exception('Unable to create new Document Snippet.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Folder\In) {
                $wsDocument->type = 'folder';
                $document = new Document\Folder();

                return $this->create($wsDocument, $document);
            }
            throw new \Exception('Unable to create new Document Folder.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentLink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Link\In) {
                $wsDocument->type = 'link';
                $document = new Document\Link();

                return $this->create($wsDocument, $document);
            }
            throw new \Exception('Unable to create new Document Link.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createDocumentHardlink($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Document\Hardlink\In) {
                $wsDocument->type = 'hardlink';
                $document = new Document\Hardlink();

                return $this->create($wsDocument, $document);
            }
            throw new \Exception('Unable to create new Document Hardlink.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createAssetFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Asset\Folder\In) {
                $wsDocument->type = 'folder';
                $asset = new Asset\Folder();

                return $this->create($wsDocument, $asset);
            }
            throw new \Exception('Unable to create new Asset Folder.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createAssetFile($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\Asset\File\In) {
                $type = $wsDocument->type;
                if (!empty($type)) {
                    $type = '\\Pimcore\\Model\\Asset\\' . ucfirst($type);
                    $asset = new $type();
                    //TODO: maybe introduce an Asset\AbstractAsset from which all Asset\Files should be derived
                    if ($asset instanceof Asset and !$asset instanceof Asset\Folder) {
                        return $this->create($wsDocument, $asset);
                    } else {
                        throw new \Exception("Unable to create new Asset File, could not instantiate Asset with given type[ $type ]");
                    }
                } else {
                    throw new \Exception('Unable to create new Asset File, no type  provided');
                }
            }

            throw new \Exception('Unable to create new Asset File.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createObjectFolder($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\DataObject\Folder\In) {
                $wsDocument->type = 'folder';
                $object = new DataObject\Folder();

                return $this->create($wsDocument, $object);
            }

            throw new \Exception('Unable to create new Object Folder.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    public function createObjectConcrete($wsDocument)
    {
        try {
            if ($wsDocument instanceof Webservice\Data\DataObject\Concrete\In) {
                $className = 'Pimcore\\Model\\DataObject\\' . ucfirst($wsDocument->className);
                $object = \Pimcore::getContainer()->get('pimcore.model.factory')->build($className);
                if ($object instanceof DataObject\Concrete) {
                    return $this->create($wsDocument, $object);
                } else {
                    throw new \Exception("Unable to create new Object Concrete, could not instantiate Object with given class name [ $classname ]");
                }
            }

            throw new \Exception('Unable to create new Object Concrete.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getAssetFolderById($id)
    {
        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset\Folder) {
                $className = Webservice\Data\Mapper::findWebserviceClass($asset, 'out');
                $apiAsset = Webservice\Data\Mapper::map($asset, $className, 'out');

                return $apiAsset;
            }

            throw new \Exception('Asset Folder with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     * @param null $options
     *
     * @throws \Exception
     */
    public function getAssetFileById($id, $options = null)
    {
        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset) {
                $apiAsset = Webservice\Data\Mapper::map($asset, '\\Pimcore\\Model\\Webservice\\Data\\Asset\\File\\Out', 'out', $options);

                return $apiAsset;
            }

            throw new \Exception('Asset File with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     *
     * @throws \Exception
     */
    public function getAssetList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null)
    {
        try {
            $params = [];

            if (!empty($condition)) {
                $params['condition'] = $condition;
            }
            if (!empty($order)) {
                $params['order'] = $order;
            }
            if (!empty($orderKey)) {
                $params['orderKey'] = $orderKey;
            }
            if (!empty($offset)) {
                $params['offset'] = $offset;
            }
            if (!empty($limit)) {
                $params['limit'] = $limit;
            }
            if (!empty($groupBy)) {
                $params['groupBy'] = $groupBy;
            }

            $list = Asset::getList($params);

            $items = [];
            foreach ($list as $asset) {
                $item = new Webservice\Data\Asset\Listing\Item();
                $item->id = $asset->getId();
                $item->type = $asset->getType();

                $items[] = $item;
            }

            return $items;
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function deleteAsset($id)
    {
        try {
            $asset = Asset::getById($id);
            if ($asset instanceof Asset) {
                $asset->delete();

                return true;
            }

            throw new \Exception('Asset with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getObjectFolderById($id)
    {
        try {
            $folder = DataObject::getById($id);
            if ($folder instanceof DataObject\Folder) {
                $apiFolder = Webservice\Data\Mapper::map($folder, '\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Folder\\Out', 'out');

                return $apiFolder;
            }

            throw new \Exception('Object Folder with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getObjectConcreteById($id)
    {
        try {
            $object = DataObject::getById($id);

            if ($object instanceof DataObject\Concrete) {
                // load all data (eg. lazy loaded fields like multihref, object, ...)
                DataObject\Service::loadAllObjectFields($object);
                $apiObject = Webservice\Data\Mapper::map($object, '\\Pimcore\\Model\\Webservice\\Data\\DataObject\\Concrete\\Out', 'out');

                return $apiObject;
            }

            throw new \Exception('Object with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param null $condition
     * @param null $order
     * @param null $orderKey
     * @param null $offset
     * @param null $limit
     * @param null $groupBy
     * @param null $objectClass
     *
     * @throws \Exception
     */
    public function getObjectList($condition = null, $order = null, $orderKey = null, $offset = null, $limit = null, $groupBy = null, $objectClass = null)
    {
        try {
            $params = ['objectTypes' => [Object\AbstractObject::OBJECT_TYPE_FOLDER, DataObject\AbstractObject::OBJECT_TYPE_OBJECT, DataObject\AbstractObject::OBJECT_TYPE_VARIANT]];

            if (!empty($condition)) {
                $params['condition'] = $condition;
            }
            if (!empty($order)) {
                $params['order'] = $order;
            }
            if (!empty($orderKey)) {
                $params['orderKey'] = $orderKey;
            }
            if (!empty($offset)) {
                $params['offset'] = $offset;
            }
            if (!empty($limit)) {
                $params['limit'] = $limit;
            }
            if (!empty($groupBy)) {
                $params['groupBy'] = $groupBy;
            }

            $listClassName = '\\Pimcore\\Model\\Object';
            if (!empty($objectClass)) {
                $listClassName = '\\Pimcore\\Model\\DataObject\\' . ucfirst($objectClass);
                if (!\Pimcore\Tool::classExists($listClassName)) {
                    $listClassName = '\\Pimcore\\Model\\Object';
                }
            }

            $list = $listClassName::getList($params);
            $list->setUnpublished(1);

            $items = [];
            foreach ($list as $object) {
                $item = new Webservice\Data\DataObject\Listing\Item();
                $item->id = $object->getId();
                $item->type = $object->getType();
                if (method_exists($object, 'getPublished')) {
                    $item->published = $object->getPublished();
                }

                $items[] = $item;
            }

            return $items;
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function unpublishObject($id)
    {
        try {
            $object = DataObject\AbstractObject::getById($id);
            if ($object instanceof DataObject\AbstractObject) {
                $object->setPublished(false);
                $object->save();

                return true;
            }

            throw new \Exception('Object with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function deleteObject($id)
    {
        try {
            $object = DataObject\AbstractObject::getById($id);
            if ($object instanceof DataObject\AbstractObject) {
                $object->delete();

                return true;
            }

            throw new \Exception('Object with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $wsDocument
     * @param $element
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function create($wsDocument, $element)
    {
        $wsDocument->reverseMap($element);
        $element->setId(null);
        $element->setCreationDate(time());
        $this->setModificationParams($element, true);
        $key = $element->getKey();
        if (empty($key)) {
            throw new \Exception('Cannot create element without key');
        }

        $element->save();

        return $element->getId();
    }

    /**
     * @param $element
     * @param $key
     * @param $path
     *
     * @return string
     */
    protected function getSaveCopyName($element, $key, $path)
    {
        if ($element instanceof DataObject\AbstractObject) {
            $equal = DataObject\AbstractObject::getByPath($path . '/' . $key);
        } elseif ($element instanceof Document) {
            $equal = Document::getByPath($path . '/' . $key);
        } elseif ($element instanceof Asset) {
            $equal = Asset::getByPath($path . '/' . $key);
        }

        if ($equal) {
            $key .= '_WScopy';

            return $this->getSaveCopyName($element, $key, $path);
        }

        return $key;
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    protected function updateDocument($wsDocument)
    {
        $document = Document::getById($wsDocument->id);

        if ($document === null) {
            throw new \Exception('Document with given ID (' . $wsDocument->id . ') does not exist.');
        }

        $this->setModificationParams($document, false);

        if ($document instanceof Document and strtolower($wsDocument->type) == $document->getType()) {
            $wsDocument->reverseMap($document);
            $document->save();

            return true;
        } else {
            throw new \Exception('Type mismatch for given document with ID [' . $wsDocument->id . '] and existing document with id [' . $document->getId() . ']');
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    protected function updateObject($wsDocument)
    {
        $object = DataObject\AbstractObject::getById($wsDocument->id);

        if ($object === null) {
            throw new \Exception('Object with given ID (' . $wsDocument->id . ') does not exist.');
        }

        $this->setModificationParams($object, false);
        if ($object instanceof DataObject\Concrete and $object->getClassName() == $wsDocument->className) {
            $wsDocument->reverseMap($object);
            $object->save();

            return true;
        } elseif ($object instanceof DataObject\Folder and $object->getType() == strtolower($wsDocument->type)) {
            $wsDocument->reverseMap($object);
            $object->save();

            return true;
        } else {
            throw new \Exception('Type/Class mismatch for given object with ID [' . $wsDocument->id . '] and existing object with id [' . $object->getId() . ']');
        }
    }

    /**
     * @param $wsDocument
     *
     * @throws \Exception
     */
    protected function updateAsset($wsDocument)
    {
        $asset = Asset::getById($wsDocument->id);

        if ($asset === null) {
            throw new \Exception('Asset with given ID (' . $wsDocument->id . ') does not exist.');
        }

        $this->setModificationParams($asset, false);
        if ($asset instanceof Asset and $asset->getType() == strtolower($wsDocument->type)) {
            $wsDocument->reverseMap($asset);
            $asset->save();

            return true;
        } else {
            throw new \Exception('Type mismatch for given asset with ID [' . $wsDocument->id . '] and existing asset with id [' . $asset->getId() . ']');
        }
    }

    /**
     * @param $element
     * @param bool $creation
     *
     * @return $this
     *
     * @throws \Exception
     */
    protected function setModificationParams($element, $creation = false)
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \Exception('No user present');
        }
        if ($creation) {
            $element->setUserOwner($user->getId());
        }
        $element->setUserModification($user->getId());
        $element->setModificationDate(time());

        return $this;
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getClassById($id)
    {
        try {
            $class = DataObject\ClassDefinition::getById($id);
            if ($class instanceof DataObject\ClassDefinition) {
                $apiClass = Webservice\Data\Mapper::map($class, '\\Pimcore\\Model\\Webservice\\Data\\ClassDefinition\\Out', 'out');
                unset($apiClass->fieldDefinitions);

                return $apiClass;
            }

            throw new \Exception('Class with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $id
     *
     * @throws \Exception
     */
    public function getObjectMetadataById($id)
    {
        try {
            $object = DataObject\Concrete::getById($id);

            if ($object instanceof DataObject\Concrete) {
                // load all data (eg. lazy loaded fields like multihref, object, ...)
                $classId = $object->getClassId();

                return $this->getClassById($classId);
            }

            throw new \Exception('Object with given ID (' . $id . ') does not exist.');
        } catch (\Exception $e) {
            Logger::error($e);
            throw $e;
        }
    }

    /**
     * @param $type
     * @param $params
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getTranslations($type, $params)
    {
        if (in_array($type, ['website', 'admin'])) {
            $listClass = '\\Pimcore\\Model\\Translation\\' . ucfirst($type) .'\\Listing';
            /**
             * @var $list \Pimcore\Model\Translation\Website\Listing
             */
            $list = new $listClass();
            if ($key = $params['key']) {
                $list->addConditionParam(' `key` LIKE ' . \Pimcore\Db::get()->quote('%' . $key . '%'), '');
            }

            $list->addConditionParam(' `creationDate` >= ? ', $params['creationDateFrom']);
            $list->addConditionParam(' `creationDate` <= ? ', $params['creationDateTill']);

            $list->addConditionParam(' `modificationDate` >= ? ', $params['modificationDateFrom']);
            $list->addConditionParam(' `modificationDate` <= ? ', $params['modificationDateTill']);
            $data = $list->load();

            $result = [];
            foreach ($data as $obj) {
                $result[] = $obj->getForWebserviceExport();
            }

            return $result;
        } else {
            throw new \Exception("Parameter 'type' has to be 'website' or 'admin'");
        }
    }
}
