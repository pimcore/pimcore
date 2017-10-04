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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin;

use Pimcore\Controller\Configuration\TemplatePhp;
use Pimcore\Controller\EventedControllerInterface;
use Pimcore\Event\AdminEvents;
use Pimcore\File;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Asset;
use Pimcore\Model\Element;
use Pimcore\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/asset")
 */
class AssetController extends ElementControllerBase implements EventedControllerInterface
{
    /**
     * @var Asset\Service
     */
    protected $_assetService;

    /**
     * @Route("/get-data-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getDataByIdAction(Request $request)
    {

        // check for lock
        if (Element\Editlock::isLocked($request->get('id'), 'asset')) {
            return $this->json([
                'editlock' => Element\Editlock::getByElement($request->get('id'), 'asset')
            ]);
        }
        Element\Editlock::lock($request->get('id'), 'asset');

        $asset = Asset::getById(intval($request->get('id')));
        $asset = clone $asset;

        if (!$asset instanceof Asset) {
            return $this->json(['success' => false, 'message' => "asset doesn't exist"]);
        }

        $asset->setMetadata(Asset\Service::expandMetadataForEditmode($asset->getMetadata()));
        $asset->setProperties(Element\Service::minimizePropertiesForEditmode($asset->getProperties()));
        //$asset->getVersions();
        $asset->getScheduledTasks();
        $asset->idPath = Element\Service::getIdPath($asset);
        $asset->userPermissions = $asset->getUserPermissions();
        $asset->setLocked($asset->isLocked());
        $asset->setParent(null);

        if ($asset instanceof Asset\Text) {
            if ($asset->getFileSize() < 2000000) {
                // it doesn't make sense to show a preview for files bigger than 2MB
                $asset->data =  \ForceUTF8\Encoding::toUTF8($asset->getData());
            } else {
                $asset->data = false;
            }
        }

        if ($asset instanceof Asset\Image) {
            $imageInfo = [];

            if ($asset->getWidth() && $asset->getHeight()) {
                $imageInfo['dimensions'] = [];
                $imageInfo['dimensions']['width'] = $asset->getWidth();
                $imageInfo['dimensions']['height'] = $asset->getHeight();
            }

            $exifData = $asset->getEXIFData();
            if (!empty($exifData)) {
                $imageInfo['exif'] = $exifData;
            }

            $iptcData = $asset->getIPTCData();
            if (!empty($iptcData)) {
                // flatten data, to be displayed in grid
                foreach ($iptcData as &$value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                }

                $imageInfo['iptc'] = $iptcData;
            }

            $imageInfo['exiftoolAvailable'] = (bool) \Pimcore\Tool\Console::getExecutable('exiftool');

            $asset->imageInfo = $imageInfo;
        }

        $asset->setStream(null);

        //Hook for modifying return value - e.g. for changing permissions based on object data
        //data need to wrapped into a container in order to pass parameter to event listeners by reference so that they can change the values
        $data = object2array($asset);
        $event = new GenericEvent($this, [
            'data' => $data,
            'asset' => $asset
        ]);
        \Pimcore::getEventDispatcher()->dispatch(AdminEvents::ASSET_GET_PRE_SEND_DATA, $event);
        $data = $event->getArgument('data');

        if ($asset->isAllowed('view')) {
            return $this->json($data);
        }

        return $this->json(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/tree-get-childs-by-id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function treeGetChildsByIdAction(Request $request)
    {
        $assets = [];
        $cv = false;
        $asset = Asset::getById($request->get('node'));

        $limit = intval($request->get('limit'));
        if (!$request->get('limit')) {
            $limit = 100000000;
        }
        $offset = intval($request->get('start'));

        if ($asset->hasChildren()) {
            if ($request->get('view')) {
                $cv = \Pimcore\Model\Element\Service::getCustomViewById($request->get('view'));
            }

            // get assets
            $childsList = new Asset\Listing();
            if ($this->getUser()->isAdmin()) {
                $childsList->setCondition('parentId = ? ', $asset->getId());
            } else {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $childsList->setCondition('parentId = ? and
                    (
                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path,filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    or
                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path,filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                    )', $asset->getId());
            }
            $childsList->setLimit($limit);
            $childsList->setOffset($offset);
            $childsList->setOrderKey("FIELD(assets.type, 'folder') DESC, assets.filename ASC", false);

            \Pimcore\Model\Element\Service::addTreeFilterJoins($cv, $childsList);
            $childs = $childsList->load();

            foreach ($childs as $childAsset) {
                if ($childAsset->isAllowed('list')) {
                    $assets[] = $this->getTreeNodeConfig($childAsset);
                }
            }
        }

        if ($request->get('limit')) {
            return $this->json([
                'offset' => $offset,
                'limit' => $limit,
                'total' => $asset->getChildAmount($this->getUser()),
                'nodes' => $assets
            ]);
        } else {
            return $this->json($assets);
        }
    }

    /**
     * @Route("/add-asset")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAssetAction(Request $request)
    {
        $res = $this->addAsset($request);

        return $this->json(['success' => $res['success'], 'msg' => 'Success']);
    }

    /**
     * @Route("/add-asset-compatibility")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addAssetCompatibilityAction(Request $request)
    {
        // this is a special action for the compatibility mode upload (without flash)
        $res = $this->addAsset($request);

        $response = $this->json([
            'success' => $res['success'],
            'msg' => $res['success'] ? 'Success' : 'Error',
            'id' => $res['asset'] ? $res['asset']->getId() : null,
            'fullpath' => $res['asset'] ? $res['asset']->getRealFullPath() : null,
            'type' => $res['asset'] ? $res['asset']->getType() : null
        ]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    /**
     * @param Request $request
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function addAsset(Request $request)
    {
        $success = false;

        if (array_key_exists('Filedata', $_FILES)) {
            $filename = $_FILES['Filedata']['name'];
            $sourcePath = $_FILES['Filedata']['tmp_name'];
        } elseif ($request->get('type') == 'base64') {
            $filename = $request->get('filename');
            $sourcePath = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/upload-base64' . uniqid() . '.tmp';
            $data = preg_replace('@^data:[^,]+;base64,@', '', $request->get('data'));
            File::put($sourcePath, base64_decode($data));
        }

        $parentId = $request->get('parentId');

        if ($request->get('dir') && $request->get('parentId')) {
            // this is for uploading folders with Drag&Drop
            // param "dir" contains the relative path of the file
            $parent = Asset::getById($request->get('parentId'));
            $newPath = $parent->getRealFullPath() . '/' . trim($request->get('dir'), '/ ');

            // check if the path is outside of the asset directory
            $newRealPath = PIMCORE_ASSET_DIRECTORY . $newPath;
            $newRealPath = resolvePath($newRealPath);
            if (strpos($newRealPath, PIMCORE_ASSET_DIRECTORY) !== 0) {
                throw new \Exception('not allowed');
            }

            $maxRetries = 5;
            for ($retries=0; $retries < $maxRetries; $retries++) {
                try {
                    $newParent = Asset\Service::createFolderByPath($newPath);
                    break;
                } catch (\Exception $e) {
                    if ($retries < ($maxRetries - 1)) {
                        $waitTime = rand(100000, 900000); // microseconds
                        usleep($waitTime); // wait specified time until we restart the transaction
                    } else {
                        // if the transaction still fail after $maxRetries retries, we throw out the exception
                        throw $e;
                    }
                }
            }
            $parentId = $newParent->getId();
        } elseif (!$request->get('parentId') && $request->get('parentPath')) {
            $parent = Asset::getByPath($request->get('parentPath'));
            if ($parent instanceof Asset\Folder) {
                $parentId = $parent->getId();
            } else {
                $parentId = 1;
            }
        } elseif (!$request->get('parentId')) {
            // set the parent to the root folder
            $parentId = 1;
        }

        $filename = Element\Service::getValidKey($filename, 'asset');
        if (empty($filename)) {
            throw new \Exception('The filename of the asset is empty');
        }

        $parentAsset = Asset::getById(intval($parentId));

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if ($parentAsset->isAllowed('create')) {
            if (!is_file($sourcePath) || filesize($sourcePath) < 1) {
                throw new \Exception('Something went wrong, please check upload_max_filesize and post_max_size in your php.ini and write permissions of ' . PIMCORE_PUBLIC_VAR);
            }

            $asset = Asset::create($parentId, [
                'filename' => $filename,
                'sourcePath' => $sourcePath,
                'userOwner' => $this->getUser()->getId(),
                'userModification' => $this->getUser()->getId()
            ]);
            $success = true;

            @unlink($sourcePath);
        } else {
            Logger::debug('prevented creating asset because of missing permissions, parent asset is ' . $parentAsset->getRealFullPath());
        }

        return [
            'success' => $success,
            'asset' => $asset
        ];
    }

    /**
     * @param $targetPath
     * @param $filename
     *
     * @return string
     */
    protected function getSafeFilename($targetPath, $filename)
    {
        $originalFilename = $filename;
        $count = 1;

        if ($targetPath == '/') {
            $targetPath = '';
        }

        while (true) {
            if (Asset\Service::pathExists($targetPath . '/' . $filename)) {
                $filename = str_replace('.' . File::getFileExtension($originalFilename), '_' . $count . '.' . File::getFileExtension($originalFilename), $originalFilename);
                $count++;
            } else {
                return $filename;
            }
        }
    }

    /**
     * @Route("/replace-asset")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function replaceAssetAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        $newFilename = Element\Service::getValidKey($_FILES['Filedata']['name'], 'asset');
        $mimetype = Tool\Mime::detect($_FILES['Filedata']['tmp_name'], $newFilename);
        $newType = Asset::getTypeFromMimeMapping($mimetype, $newFilename);

        if ($newType != $asset->getType()) {
            $translator = $this->get('translator');

            return $this->json([
                'success'=>false,
                'message'=> sprintf($translator->trans('asset_type_change_not_allowed', [], 'admin'), $asset->getType(), $newType)
            ]);
        }

        $stream = fopen($_FILES['Filedata']['tmp_name'], 'r+');
        $asset->setStream($stream);
        $asset->setCustomSetting('thumbnails', null);
        $asset->setUserModification($this->getUser()->getId());
        $newFilename = Element\Service::getValidKey($_FILES['Filedata']['name'], 'asset');
        if ($newFilename != $asset->getFilename()) {
            $newFilename = Element\Service::getSaveCopyName('asset', $newFilename, $asset->getParent());
        }
        $asset->setFilename($newFilename);

        if ($asset->isAllowed('publish')) {
            $asset->save();

            $response = $this->json([
                'id' => $asset->getId(),
                'path' => $asset->getRealFullPath(),
                'success' => true
            ]);

            // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
            // Ext.form.Action.Submit and mark the submission as failed
            $response->headers->set('Content-Type', 'text/html');

            return $response;
        } else {
            throw new \Exception('missing permission');
        }
    }

    /**
     * @Route("/add-folder")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addFolderAction(Request $request)
    {
        $success = false;
        $parentAsset = Asset::getById(intval($request->get('parentId')));
        $equalAsset = Asset::getByPath($parentAsset->getRealFullPath() . '/' . $request->get('name'));

        if ($parentAsset->isAllowed('create')) {
            if (!$equalAsset) {
                $asset = Asset::create($request->get('parentId'), [
                    'filename' => $request->get('name'),
                    'type' => 'folder',
                    'userOwner' => $this->getUser()->getId(),
                    'userModification' => $this->getUser()->getId()
                ]);
                $success = true;
            }
        } else {
            Logger::debug('prevented creating asset because of missing permissions');
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/delete")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteAction(Request $request)
    {
        if ($request->get('type') == 'childs') {
            $parentAsset = Asset::getById($request->get('id'));

            $list = new Asset\Listing();
            $list->setCondition('path LIKE ?', [$parentAsset->getRealFullPath() . '/%']);
            $list->setLimit(intval($request->get('amount')));
            $list->setOrderKey('LENGTH(path)', false);
            $list->setOrder('DESC');

            $assets = $list->load();

            $deletedItems = [];
            foreach ($assets as $asset) {
                /**
                 * @var $asset Asset
                 */
                $deletedItems[] = $asset->getRealFullPath();
                if ($asset->isAllowed('delete')) {
                    $asset->delete();
                }
            }

            return $this->json(['success' => true, 'deleted' => $deletedItems]);
        } elseif ($request->get('id')) {
            $asset = Asset::getById($request->get('id'));

            if ($asset->isAllowed('delete')) {
                $asset->delete();

                return $this->json(['success' => true]);
            }
        }

        return $this->json(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @Route("/delete-info")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function deleteInfoAction(Request $request)
    {
        $hasDependency = false;
        $deleteJobs = [];
        $recycleJobs = [];

        $totalChilds = 0;

        $ids = $request->get('id');
        $ids = explode(',', $ids);

        foreach ($ids as $id) {
            try {
                $asset = Asset::getById($id);
                if (!$asset) {
                    continue;
                }
                $hasDependency = $asset->getDependencies()->isRequired();
            } catch (\Exception $e) {
                Logger::err('failed to access asset with id: ' . $id);
                continue;
            }

            // check for childs
            if ($asset instanceof Asset) {
                $recycleJobs[] = [[
                    'url' => '/admin/recyclebin/add',
                    'params' => [
                        'type' => 'asset',
                        'id' => $asset->getId()
                    ]
                ]];

                $hasChilds = $asset->hasChildren();
                if (!$hasDependency) {
                    $hasDependency = $hasChilds;
                }

                $childs = 0;
                if ($hasChilds) {
                    // get amount of childs
                    $list = new Asset\Listing();
                    $list->setCondition('path LIKE ?', [$asset->getRealFullPath() . '/%']);
                    $childs = $list->getTotalCount();
                    $totalChilds += $childs;

                    if ($childs > 0) {
                        $deleteObjectsPerRequest = 5;
                        for ($i = 0; $i < ceil($childs / $deleteObjectsPerRequest); $i++) {
                            $deleteJobs[] = [[
                                'url' => '/admin/asset/delete',
                                'params' => [
                                    'step' => $i,
                                    'amount' => $deleteObjectsPerRequest,
                                    'type' => 'childs',
                                    'id' => $asset->getId()
                                ]
                            ]];
                        }
                    }
                }

                // the asset itself is the last one
                $deleteJobs[] = [[
                    'url' => '/admin/asset/delete',
                    'params' => [
                        'id' => $asset->getId()
                    ]
                ]];
            }
        }

        // get the element key in case of just one
        $elementKey = false;
        if (count($ids) === 1) {
            $elementKey = Asset::getById($id)->getKey();
        }

        $deleteJobs = array_merge($recycleJobs, $deleteJobs);

        return $this->json([
            'hasDependencies' => $hasDependency,
            'childs' => $totalChilds,
            'deletejobs' => $deleteJobs,
            'batchDelete' => count($ids) > 1,
            'elementKey' => $elementKey
        ]);
    }

    /**
     * @param $element
     *
     * @return array
     */
    protected function getTreeNodeConfig($element)
    {
        /**
         * @var $asset Asset
         */
        $asset = $element;

        $tmpAsset = [
            'id' => $asset->getId(),
            'text' => $asset->getFilename(),
            'type' => $asset->getType(),
            'path' => $asset->getRealFullPath(),
            'basePath' => $asset->getRealPath(),
            'locked' => $asset->isLocked(),
            'lockOwner' => $asset->getLocked() ? true : false,
            'elementType' => 'asset',
            'permissions' => [
                'remove' => $asset->isAllowed('delete'),
                'settings' => $asset->isAllowed('settings'),
                'rename' => $asset->isAllowed('rename'),
                'publish' => $asset->isAllowed('publish'),
                'view' => $asset->isAllowed('view')
            ]
        ];

        // set type specific settings
        if ($asset->getType() == 'folder') {
            $tmpAsset['leaf'] = false;
            $tmpAsset['expanded'] = $asset->hasNoChilds();
            $tmpAsset['loaded'] = $asset->hasNoChilds();
            $tmpAsset['iconCls'] = 'pimcore_icon_folder';
            $tmpAsset['permissions']['create'] = $asset->isAllowed('create');

            $folderThumbs = [];
            $children = new Asset\Listing();
            $children->setCondition('path LIKE ?', [$asset->getRealFullPath() . '/%']);
            $children->setLimit(35);

            foreach ($children as $child) {
                if ($child->isAllowed('view')) {
                    if ($thumbnailUrl = $this->getThumbnailUrl($child)) {
                        $folderThumbs[] = $thumbnailUrl;
                    }
                }
            }

            if (!empty($folderThumbs)) {
                $tmpAsset['thumbnails'] = $folderThumbs;
            }
        } else {
            $tmpAsset['leaf'] = true;
            $tmpAsset['expandable'] = false;
            $tmpAsset['expanded'] = false;

            $tmpAsset['iconCls'] = 'pimcore_icon_asset_default';

            $fileExt = File::getFileExtension($asset->getFilename());
            if ($fileExt) {
                $tmpAsset['iconCls'] .= ' pimcore_icon_' . File::getFileExtension($asset->getFilename());
            }
        }

        $tmpAsset['qtipCfg'] = [
            'title' => 'ID: ' . $asset->getId()
        ];

        if ($asset->getType() == 'image') {
            try {
                $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset);

                // this is for backward-compatibility, to calculate the dimensions if they are not there
                if (!$asset->getCustomSetting('imageDimensionsCalculated')) {
                    $asset->save();
                }

                // we need the dimensions for the wysiwyg editors, so that they can resize the image immediately
                if ($asset->getCustomSetting('imageWidth') && $asset->getCustomSetting('imageHeight')) {
                    $tmpAsset['imageWidth'] = $asset->getCustomSetting('imageWidth');
                    $tmpAsset['imageHeight'] = $asset->getCustomSetting('imageHeight');
                }
            } catch (\Exception $e) {
                Logger::debug('Cannot get dimensions of image, seems to be broken.');
            }
        } elseif ($asset->getType() == 'video') {
            try {
                if (\Pimcore\Video::isAvailable()) {
                    $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                Logger::debug('Cannot get dimensions of video, seems to be broken.');
            }
        } elseif ($asset->getType() == 'document') {
            try {
                // add the PDF check here, otherwise the preview layer in admin is shown without content
                if (\Pimcore\Document::isAvailable() && \Pimcore\Document::isFileTypeSupported($asset->getFilename())) {
                    $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset);
                }
            } catch (\Exception $e) {
                Logger::debug('Cannot get dimensions of video, seems to be broken.');
            }
        }

        $tmpAsset['cls'] = '';
        if ($asset->isLocked()) {
            $tmpAsset['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($asset->getLocked()) {
            $tmpAsset['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        return $tmpAsset;
    }

    /**
     * @param Asset $asset
     *
     * @return null|string
     */
    protected function getThumbnailUrl(Asset $asset)
    {
        if ($asset instanceof Asset\Image) {
            return '/admin/asset/get-image-thumbnail?id=' . $asset->getId() . '&treepreview=true';
        } elseif ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
            return '/admin/asset/get-video-thumbnail?id=' . $asset->getId() . '&treepreview=true';
        } elseif ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
            return '/admin/asset/get-document-thumbnail?id=' . $asset->getId() . '&treepreview=true';
        }

        return null;
    }

    /**
     * @Route("/update")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function updateAction(Request $request)
    {
        $success = false;
        $allowUpdate = true;

        $updateData = array_merge($request->request->all(), $request->query->all());

        $asset = Asset::getById($request->get('id'));
        if ($asset->isAllowed('settings')) {
            $asset->setUserModification($this->getUser()->getId());

            // if the position is changed the path must be changed || also from the childs
            if ($request->get('parentId')) {
                $parentAsset = Asset::getById($request->get('parentId'));

                //check if parent is changed i.e. asset is moved
                if ($asset->getParentId() != $parentAsset->getId()) {
                    if (!$parentAsset->isAllowed('create')) {
                        throw new \Exception('Prevented moving asset - no create permission on new parent ');
                    }

                    $intendedPath = $parentAsset->getRealPath();
                    $pKey = $parentAsset->getKey();
                    if (!empty($pKey)) {
                        $intendedPath .= $parentAsset->getKey() . '/';
                    }

                    $assetWithSamePath = Asset::getByPath($intendedPath . $asset->getKey());

                    if ($assetWithSamePath != null) {
                        $allowUpdate = false;
                    }

                    if ($asset->isLocked()) {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                if ($request->get('filename') != $asset->getFilename() and !$asset->isAllowed('rename')) {
                    unset($updateData['filename']);
                    Logger::debug('prevented renaming asset because of missing permissions ');
                }

                $asset->setValues($updateData);

                try {
                    $asset->save();
                    $success = true;
                } catch (\Exception $e) {
                    return $this->json(['success' => false, 'message' => $e->getMessage()]);
                }
            } else {
                $msg = 'prevented moving asset, asset with same path+key already exists at target location or the asset is locked. ID: ' . $asset->getId();
                Logger::debug($msg);

                return $this->json(['success' => $success, 'message' => $msg]);
            }
        } elseif ($asset->isAllowed('rename') && $request->get('filename')) {
            //just rename
            try {
                $asset->setFilename($request->get('filename'));
                $asset->save();
                $success = true;
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            Logger::debug('prevented update asset because of missing permissions ');
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/webdav{path}", requirements={"path"=".*"}, name="pimcore_admin_webdav")
     *
     * @param Request $request
     */
    public function webdavAction(Request $request)
    {
        $homeDir = Asset::getById(1);

        try {
            $publicDir = new Asset\WebDAV\Folder($homeDir);
            $objectTree = new Asset\WebDAV\Tree($publicDir);
            $server = new \Sabre\DAV\Server($objectTree);
            $server->setBaseUri('/admin/asset/webdav/');

            // lock plugin
            $lockBackend = new \Sabre\DAV\Locks\Backend\File(PIMCORE_SYSTEM_TEMP_DIRECTORY . '/webdav-locks.dat');
            $lockPlugin = new \Sabre\DAV\Locks\Plugin($lockBackend);
            $server->addPlugin($lockPlugin);

            // sync plugin
            $server->addPlugin(new \Sabre\DAV\Sync\Plugin());

            // browser plugin
            $server->addPlugin(new \Sabre\DAV\Browser\Plugin());

            $server->exec();
        } catch (\Exception $e) {
            Logger::error($e);
        }

        exit;
    }

    /**
     * @Route("/save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request)
    {
        try {
            $success = false;
            if ($request->get('id')) {
                $asset = Asset::getById($request->get('id'));
                if ($asset->isAllowed('publish')) {

                    // metadata
                    if ($request->get('metadata')) {
                        $metadata = $this->decodeJson($request->get('metadata'));
                        $metadata = Asset\Service::minimizeMetadata($metadata);
                        $asset->setMetadata($metadata);
                    }

                    // properties
                    if ($request->get('properties')) {
                        $properties = [];
                        $propertiesData = $this->decodeJson($request->get('properties'));

                        if (is_array($propertiesData)) {
                            foreach ($propertiesData as $propertyName => $propertyData) {
                                $value = $propertyData['data'];

                                try {
                                    $property = new Model\Property();
                                    $property->setType($propertyData['type']);
                                    $property->setName($propertyName);
                                    $property->setCtype('asset');
                                    $property->setDataFromEditmode($value);
                                    $property->setInheritable($propertyData['inheritable']);

                                    $properties[$propertyName] = $property;
                                } catch (\Exception $e) {
                                    Logger::err("Can't add " . $propertyName . ' to asset ' . $asset->getRealFullPath());
                                }
                            }

                            $asset->setProperties($properties);
                        }
                    }

                    // scheduled tasks
                    if ($request->get('scheduler')) {
                        $tasks = [];
                        $tasksData = $this->decodeJson($request->get('scheduler'));

                        if (!empty($tasksData)) {
                            foreach ($tasksData as $taskData) {
                                $taskData['date'] = strtotime($taskData['date'] . ' ' . $taskData['time']);

                                $task = new Model\Schedule\Task($taskData);
                                $tasks[] = $task;
                            }
                        }

                        $asset->setScheduledTasks($tasks);
                    }

                    if ($request->get('data')) {
                        $asset->setData($request->get('data'));
                    }

                    $asset->setUserModification($this->getUser()->getId());

                    try {
                        $asset->save();
                        $asset->getData();
                        $success = true;
                    } catch (\Exception $e) {
                        if ($e instanceof Element\ValidationException) {
                            throw $e;
                        }

                        return $this->json(['success' => false, 'message' => $e->getMessage()]);
                    }
                } else {
                    Logger::debug('prevented save asset because of missing permissions ');
                }

                return $this->json(['success' => $success]);
            }

            return $this->json(false);
        } catch (\Exception $e) {
            Logger::log($e);
            if ($e instanceof Element\ValidationException) {
                return $this->json(['success' => false, 'type' => 'ValidationException', 'message' => $e->getMessage(), 'stack' => $e->getTraceAsString(), 'code' => $e->getCode()]);
            }
            throw $e;
        }
    }

    /**
     * @Route("/publish-version")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function publishVersionAction(Request $request)
    {
        $version = Model\Version::getById($request->get('id'));
        $asset = $version->loadData();

        $currentAsset = Asset::getById($asset->getId());
        if ($currentAsset->isAllowed('publish')) {
            try {
                $asset->setUserModification($this->getUser()->getId());
                $asset->save();

                return $this->json(['success' => true]);
            } catch (\Exception $e) {
                return $this->json(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->json(false);
    }

    /**
     * @Route("/show-version")
     * @TemplatePhp()
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     */
    public function showVersionAction(Request $request)
    {
        $id = intval($request->get('id'));
        $version = Model\Version::getById($id);
        $asset = $version->loadData();

        if ($asset->isAllowed('versions')) {
            return $this->render(
                'PimcoreAdminBundle:Admin/Asset:showVersion' . ucfirst($asset->getType()) . '.html.php',
                ['asset' => $asset]
            );
        } else {
            throw new \Exception('Permission denied, version id [' . $id . ']');
        }
    }

    /**
     * @Route("/download")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function downloadAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if ($asset->isAllowed('view')) {
            $response = new BinaryFileResponse($asset->getFileSystemPath());
            $response->headers->set('Content-Type', $asset->getMimetype());
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $asset->getFilename());

            return $response;
        }
    }

    /**
     * @Route("/download-image-thumbnail")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function downloadImageThumbnailAction(Request $request)
    {
        $image = Asset\Image::getById($request->get('id'));

        if (!$image->isAllowed('view')) {
            throw new \Exception('not allowed to view thumbnail');
        }

        $config = null;

        if ($request->get('config')) {
            $config = $this->decodeJson($request->get('config'));
        } elseif ($request->get('type')) {
            $predefined = [
                'web' => [
                    'resize_mode' => 'scaleByWidth',
                    'width' => 3500,
                    'dpi' => 72,
                    'format' => 'JPEG',
                    'quality' => 85
                ],
                'print' => [
                    'resize_mode' => 'scaleByWidth',
                    'width' => 6000,
                    'dpi' => 300,
                    'format' => 'JPEG',
                    'quality' => 95
                ],
                'office' => [
                    'resize_mode' => 'scaleByWidth',
                    'width' => 1190,
                    'dpi' => 144,
                    'format' => 'JPEG',
                    'quality' => 90
                ],
            ];

            $config = $predefined[$request->get('type')];
        }

        if ($config) {
            $thumbnailConfig = new Asset\Image\Thumbnail\Config();
            $thumbnailConfig->setName('pimcore-download-' . $image->getId() . '-' . md5($request->get('config')));

            if ($config['resize_mode'] == 'scaleByWidth') {
                $thumbnailConfig->addItem('scaleByWidth', [
                    'width' => $config['width']
                ]);
            } elseif ($config['resize_mode'] == 'scaleByHeight') {
                $thumbnailConfig->addItem('scaleByHeight', [
                    'height' => $config['height']
                ]);
            } else {
                $thumbnailConfig->addItem('resize', [
                    'width' => $config['width'],
                    'height' => $config['height']
                ]);
            }

            $thumbnailConfig->setQuality($config['quality']);
            $thumbnailConfig->setFormat($config['format']);

            if ($thumbnailConfig->getFormat() == 'JPEG') {
                $thumbnailConfig->setPreserveMetaData(true);

                if (empty($config['quality'])) {
                    $thumbnailConfig->setPreserveColor(true);
                }
            }

            $thumbnail = $image->getThumbnail($thumbnailConfig);
            $thumbnailFile = $thumbnail->getFileSystemPath();

            $exiftool = \Pimcore\Tool\Console::getExecutable('exiftool');
            if ($thumbnailConfig->getFormat() == 'JPEG' && $exiftool && isset($config['dpi']) && $config['dpi']) {
                \Pimcore\Tool\Console::exec($exiftool . ' -overwrite_original -xresolution=' . $config['dpi'] . ' -yresolution=' . $config['dpi'] . ' -resolutionunit=inches ' . escapeshellarg($thumbnailFile));
            }

            $downloadFilename = str_replace(
                '.' . File::getFileExtension($image->getFilename()),
                '.' . $thumbnail->getFileExtension(),
                $image->getFilename()
            );
            $downloadFilename = strtolower($downloadFilename);

            clearstatcache();

            $response = new BinaryFileResponse($thumbnailFile);
            $response->headers->set('Content-Type', $thumbnail->getMimeType());
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $downloadFilename);
            $this->addThumbnailCacheHeaders($response);
            $response->deleteFileAfterSend(true);

            return $response;
        }
    }

    /**
     * @Route("/get-image-thumbnail")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse|JsonResponse
     */
    public function getImageThumbnailAction(Request $request)
    {
        $fileinfo = $request->get('fileinfo');
        $image = Asset\Image::getById(intval($request->get('id')));

        if (!$image->isAllowed('view')) {
            throw new \Exception('not allowed to view thumbnail');
        }

        $thumbnail = null;

        if ($request->get('thumbnail')) {
            $thumbnail = $image->getThumbnailConfig($request->get('thumbnail'));
        }
        if (!$thumbnail) {
            if ($request->get('config')) {
                $thumbnail = $image->getThumbnailConfig($this->decodeJson($request->get('config')));
            } else {
                $thumbnail = $image->getThumbnailConfig(array_merge($request->request->all(), $request->query->all()));
            }
        } else {
            // no high-res images in admin mode (editmode)
            // this is mostly because of the document's image editable, which doesn't know anything about the thumbnail
            // configuration, so the dimensions would be incorrect (double the size)
            $thumbnail->setHighResolution(1);
        }

        $format = strtolower($thumbnail->getFormat());
        if ($format == 'source' || $format == 'print') {
            $thumbnail->setFormat('PNG');
        }

        if ($request->get('treepreview')) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        if ($request->get('cropPercent')) {
            $thumbnail->addItemAt(0, 'cropPercent', [
                'width' => $request->get('cropWidth'),
                'height' => $request->get('cropHeight'),
                'y' => $request->get('cropTop'),
                'x' => $request->get('cropLeft')
            ]);

            $hash = md5(Tool\Serialize::serialize(array_merge($request->request->all(), $request->query->all())));
            $thumbnail->setName($thumbnail->getName() . '_auto_' . $hash);
        }

        $thumbnail = $image->getThumbnail($thumbnail);

        if ($fileinfo) {
            return $this->json([
                'width' => $thumbnail->getWidth(),
                'height' => $thumbnail->getHeight()]);
        }

        $thumbnailFile = $thumbnail->getFileSystemPath();

        $response = new BinaryFileResponse($thumbnailFile);
        $response->headers->set('Content-Type', $thumbnail->getMimeType());
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    /**
     * @Route("/get-video-thumbnail")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function getVideoThumbnailAction(Request $request)
    {
        if ($request->get('id')) {
            $video = Asset::getById(intval($request->get('id')));
        } elseif ($request->get('path')) {
            $video = Asset::getByPath($request->get('path'));
        }

        if (!$video->isAllowed('view')) {
            throw new \Exception('not allowed to view thumbnail');
        }

        $thumbnail = array_merge($request->request->all(), $request->query->all());

        if ($request->get('treepreview')) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $time = null;
        if ($request->get('time')) {
            $time = intval($request->get('time'));
        }

        if ($request->get('settime')) {
            $video->removeCustomSetting('image_thumbnail_asset');
            $video->setCustomSetting('image_thumbnail_time', $time);
            $video->save();
        }

        $image = null;
        if ($request->get('image')) {
            $image = Asset::getById(intval($request->get('image')));
        }

        if ($request->get('setimage') && $image) {
            $video->removeCustomSetting('image_thumbnail_time');
            $video->setCustomSetting('image_thumbnail_asset', $image->getId());
            $video->save();
        }

        $thumb = $video->getImageThumbnail($thumbnail, $time, $image);
        $thumbnailFile = $thumb->getFileSystemPath();

        $response = new BinaryFileResponse($thumbnailFile);
        $response->headers->set('Content-type', 'image/' . File::getFileExtension($thumbnailFile));

        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    /**
     * @Route("/get-document-thumbnail")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     */
    public function getDocumentThumbnailAction(Request $request)
    {
        $document = Asset::getById(intval($request->get('id')));

        if (!$document->isAllowed('view')) {
            throw new \Exception('not allowed to view thumbnail');
        }

        $thumbnail = Asset\Image\Thumbnail\Config::getByAutoDetect(array_merge($request->request->all(), $request->query->all()));

        $format = strtolower($thumbnail->getFormat());
        if ($format == 'source') {
            $thumbnail->setFormat('jpeg'); // default format for documents is JPEG not PNG (=too big)
        }

        if ($request->get('treepreview')) {
            $thumbnail = Asset\Image\Thumbnail\Config::getPreviewConfig();
        }

        $page = 1;
        if (is_numeric($request->get('page'))) {
            $page = (int)$request->get('page');
        }

        $thumb = $document->getImageThumbnail($thumbnail, $page);
        $thumbnailFile = $thumb->getFileSystemPath();

        $format = 'png';

        $response = new BinaryFileResponse($thumbnailFile);
        $response->headers->set('Content-type', 'image/' . $format);
        $this->addThumbnailCacheHeaders($response);

        return $response;
    }

    /**
     * @param Response $response
     */
    protected function addThumbnailCacheHeaders(Response $response)
    {
        $lifetime = 300;
        $date = new \DateTime('now');
        $date->add(new \DateInterval('PT' . $lifetime . 'S'));

        $response->setMaxAge($lifetime);
        $response->setPublic();
        $response->setExpires($date);
        $response->headers->set('Pragma', '');
    }

    /**
     * @Route("/get-preview-document")
     * @TemplatePhp()
     *
     * @param Request $request
     *
     * @return array
     */
    public function getPreviewDocumentAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset->isAllowed('view')) {
            throw new \Exception('not allowed to preview');
        }

        return ['asset' => $asset];
    }

    /**
     * @Route("/get-preview-video")
     * @TemplatePhp()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getPreviewVideoAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset->isAllowed('view')) {
            throw new \Exception('not allowed to preview');
        }

        $previewData = ['asset' => $asset];

        $config = Asset\Video\Thumbnail\Config::getPreviewConfig();

        $thumbnail = $asset->getThumbnail($config, ['mp4']);

        if ($thumbnail) {
            $previewData['asset'] = $asset;
            $previewData['thumbnail'] = $thumbnail;

            if ($thumbnail['status'] == 'finished') {
                return $this->render(
                    'PimcoreAdminBundle:Admin/Asset:getPreviewVideoDisplay.html.php',
                    $previewData
                );
            } else {
                return $this->render(
                    'PimcoreAdminBundle:Admin/Asset:getPreviewVideoError.html.php',
                    $previewData
                );
            }
        } else {
            return $this->render(
                'PimcoreAdminBundle:Admin/Asset:getPreviewVideoError.html.php',
                $previewData
            );
        }
    }

    /**
     * @Route("/image-editor")
     *
     * @param Request $request
     * @TemplatePhp()
     *
     * @return array
     */
    public function imageEditorAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset->isAllowed('view')) {
            throw new \Exception('not allowed to preview');
        }

        return ['asset' => $asset];
    }

    /**
     * @Route("/image-editor-save")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function imageEditorSaveAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset->isAllowed('publish')) {
            throw new \Exception('not allowed to publish');
        }

        $asset->setData(Tool::getHttpData($request->get('url')));
        $asset->setUserModification($this->getUser()->getId());
        $asset->save();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/get-folder-content-preview")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getFolderContentPreviewAction(Request $request)
    {
        $folder = Asset::getById($request->get('id'));

        $start = 0;
        $limit = 10;

        if ($request->get('limit')) {
            $limit = $request->get('limit');
        }
        if ($request->get('start')) {
            $start = $request->get('start');
        }

        $conditionFilters = [];
        $list = new Asset\Listing();
        $conditionFilters[] = 'path LIKE ' . ($folder->getRealFullPath() == '/' ? "'/%'" : $list->quote($folder->getRealFullPath() . '/%')) ." AND type != 'folder'";

        if (!$this->getUser()->isAdmin()) {
            $userIds = $this->getUser()->getRoles();
            $userIds[] = $this->getUser()->getId();
            $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
        }

        $condition = implode(' AND ', $conditionFilters);

        $list->setCondition($condition);
        $list->setLimit($limit);
        $list->setOffset($start);
        $list->setOrderKey('filename');
        $list->setOrder('asc');

        $assets = [];

        foreach ($list as $asset) {
            $thumbnailMethod = '';
            if ($asset instanceof Asset\Image) {
                $thumbnailMethod = 'getThumbnail';
            } elseif ($asset instanceof Asset\Video && \Pimcore\Video::isAvailable()) {
                $thumbnailMethod = 'getImageThumbnail';
            } elseif ($asset instanceof Asset\Document && \Pimcore\Document::isAvailable()) {
                $thumbnailMethod = 'getImageThumbnail';
            }

            if (!empty($thumbnailMethod)) {
                $filenameDisplay = $asset->getFilename();
                if (strlen($filenameDisplay) > 32) {
                    $filenameDisplay = substr($filenameDisplay, 0, 25) . '...' . \Pimcore\File::getFileExtension($filenameDisplay);
                }

                $assets[] = [
                    'id' => $asset->getId(),
                    'type' => $asset->getType(),
                    'filename' => $asset->getFilename(),
                    'filenameDisplay' => $filenameDisplay,
                    'url' => '/admin/asset/get-' . $asset->getType() . '-thumbnail?id=' . $asset->getId() . '&treepreview=true',
                    'idPath' => $data['idPath'] = Element\Service::getIdPath($asset)
                ];
            }
        }

        return $this->json([
            'assets' => $assets,
            'success' => true,
            'total' => $list->getTotalCount()
        ]);
    }

    /**
     * @Route("/copy-info")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyInfoAction(Request $request)
    {
        $transactionId = time();
        $pasteJobs = [];

        Tool\Session::useSession(function (AttributeBagInterface $session) use ($transactionId) {
            $session->set($transactionId, []);
        }, 'pimcore_copy');

        if ($request->get('type') == 'recursive') {
            $asset = Asset::getById($request->get('sourceId'));

            // first of all the new parent
            $pasteJobs[] = [[
                'url' => '/admin/asset/copy',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true
                ]
            ]];

            if ($asset->hasChildren()) {
                // get amount of children
                $list = new Asset\Listing();
                $list->setCondition('path LIKE ?', [$asset->getRealFullPath() . '/%']);
                $list->setOrderKey('LENGTH(path)', false);
                $list->setOrder('ASC');
                $childIds = $list->loadIdList();

                if (count($childIds) > 0) {
                    foreach ($childIds as $id) {
                        $pasteJobs[] = [[
                            'url' => '/admin/asset/copy',
                            'params' => [
                                'sourceId' => $id,
                                'targetParentId' => $request->get('targetId'),
                                'sourceParentId' => $request->get('sourceId'),
                                'type' => 'child',
                                'transactionId' => $transactionId
                            ]
                        ]];
                    }
                }
            }
        } elseif ($request->get('type') == 'child' || $request->get('type') == 'replace') {
            // the object itself is the last one
            $pasteJobs[] = [[
                'url' => '/admin/asset/copy',
                'params' => [
                    'sourceId' => $request->get('sourceId'),
                    'targetId' => $request->get('targetId'),
                    'type' => $request->get('type'),
                    'transactionId' => $transactionId
                ]
            ]];
        }

        return $this->json([
            'pastejobs' => $pasteJobs
        ]);
    }

    /**
     * @Route("/copy")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function copyAction(Request $request)
    {
        $success = false;
        $sourceId = intval($request->get('sourceId'));
        $source = Asset::getById($sourceId);

        $session = Tool\Session::get('pimcore_copy');
        $sessionBag = $session->get($request->get('transactionId'));

        $targetId = intval($request->get('targetId'));
        if ($request->get('targetParentId')) {
            $sourceParent = Asset::getById($request->get('sourceParentId'));

            // this is because the key can get the prefix "_copy" if the target does already exists
            if ($sessionBag['parentId']) {
                $targetParent = Asset::getById($sessionBag['parentId']);
            } else {
                $targetParent = Asset::getById($request->get('targetParentId'));
            }

            $targetPath = preg_replace('@^' . $sourceParent->getRealFullPath() . '@', $targetParent . '/', $source->getRealPath());
            $target = Asset::getByPath($targetPath);
        } else {
            $target = Asset::getById($targetId);
        }

        if ($target->isAllowed('create')) {
            $source = Asset::getById($sourceId);
            if ($source != null) {
                if ($request->get('type') == 'child') {
                    $newAsset = $this->_assetService->copyAsChild($target, $source);

                    // this is because the key can get the prefix "_copy" if the target does already exists
                    if ($request->get('saveParentId')) {
                        $sessionBag['parentId'] = $newAsset->getId();
                    }
                } elseif ($request->get('type') == 'replace') {
                    $this->_assetService->copyContents($target, $source);
                }

                $session->set($request->get('transactionId'), $sessionBag);
                Tool\Session::writeClose();

                $success = true;
            } else {
                Logger::debug('prevended copy/paste because asset with same path+key already exists in this location');
            }
        } else {
            Logger::error('could not execute copy/paste because of missing permissions on target [ ' . $targetId . ' ]');

            return $this->json(['error' => false, 'message' => 'missing_permission']);
        }

        Tool\Session::writeClose();

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/download-as-zip-jobs")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function downloadAsZipJobsAction(Request $request)
    {
        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = [];
        $asset = Asset::getById($request->get('id'));

        if ($asset->isAllowed('view')) {
            $parentPath = $asset->getRealFullPath();
            if ($asset->getId() == 1) {
                $parentPath = '';
            }

            $db = \Pimcore\Db::get();
            $conditionFilters = [];
            $conditionFilters[] .= 'path LIKE ' . $db->quote($parentPath . '/%') .' AND type != ' . $db->quote('folder');
            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
            }

            $condition = implode(' AND ', $conditionFilters);

            $assetList = new Asset\Listing();
            $assetList->setCondition($condition);
            $assetList->setOrderKey('LENGTH(path)', false);
            $assetList->setOrder('ASC');

            for ($i = 0; $i < ceil($assetList->getTotalCount() / $filesPerJob); $i++) {
                $jobs[] = [[
                    'url' => '/admin/asset/download-as-zip-add-files',
                    'params' => [
                        'id' => $asset->getId(),
                        'offset' => $i * $filesPerJob,
                        'limit' => $filesPerJob,
                        'jobId' => $jobId
                    ]
                ]];
            }
        }

        return $this->json([
            'success' => true,
            'jobs' => $jobs,
            'jobId' => $jobId
        ]);
    }

    /**
     * @Route("/download-as-zip-add-files")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function downloadAsZipAddFilesAction(Request $request)
    {
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-zip-' . $request->get('jobId') . '.zip';
        $asset = Asset::getById($request->get('id'));
        $success = false;

        if ($asset->isAllowed('view')) {
            $zip = new \ZipArchive();
            if (!is_file($zipFile)) {
                $zipState = $zip->open($zipFile, \ZipArchive::CREATE);
            } else {
                $zipState = $zip->open($zipFile);
            }

            if ($zipState === true) {
                $parentPath = $asset->getRealFullPath();
                if ($asset->getId() == 1) {
                    $parentPath = '';
                }

                $db = \Pimcore\Db::get();
                $conditionFilters = [];
                $conditionFilters[] .= "type != 'folder' AND path LIKE " . $db->quote($parentPath . '/%');
                if (!$this->getUser()->isAdmin()) {
                    $userIds = $this->getUser()->getRoles();
                    $userIds[] = $this->getUser()->getId();
                    $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
                }

                $condition = implode(' AND ', $conditionFilters);

                $assetList = new Asset\Listing();
                $assetList->setCondition($condition);
                $assetList->setOrderKey('LENGTH(path) ASC, id ASC', false);
                $assetList->setOffset((int)$request->get('offset'));
                $assetList->setLimit((int)$request->get('limit'));

                foreach ($assetList->load() as $a) {
                    if ($a->isAllowed('view')) {
                        if (!$a instanceof Asset\Folder) {
                            // add the file with the relative path to the parent directory
                            $zip->addFile($a->getFileSystemPath(), preg_replace('@^' . preg_quote($asset->getRealPath(), '@') . '@i', '', $a->getRealFullPath()));
                        }
                    }
                }

                $zip->close();
                $success = true;
            }
        }

        return $this->json([
            'success' => $success
        ]);
    }

    /**
     * @Route("/download-as-zip")
     *
     * @param Request $request
     *
     * @return BinaryFileResponse
     * Download all assets contained in the folder with parameter id as ZIP file.
     * The suggested filename is either [folder name].zip or assets.zip for the root folder.
     */
    public function downloadAsZipAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-zip-' . $request->get('jobId') . '.zip';
        $suggestedFilename = $asset->getFilename();
        if (empty($suggestedFilename)) {
            $suggestedFilename = 'assets';
        }

        $response = new BinaryFileResponse($zipFile);
        $response->headers->set('Content-Type', 'application/zip');
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $suggestedFilename . '.zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * @Route("/import-zip")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function importZipAction(Request $request)
    {
        $jobId = uniqid();
        $filesPerJob = 5;
        $jobs = [];
        $asset = Asset::getById($request->get('parentId'));

        if (!$asset->isAllowed('create')) {
            throw new \Exception('not allowed to create');
        }

        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $jobId . '.zip';

        copy($_FILES['Filedata']['tmp_name'], $zipFile);

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === true) {
            $jobAmount = ceil($zip->numFiles / $filesPerJob);
            for ($i = 0; $i < $jobAmount; $i++) {
                $jobs[] = [[
                    'url' => '/admin/asset/import-zip-files',
                    'params' => [
                        'parentId' => $asset->getId(),
                        'offset' => $i * $filesPerJob,
                        'limit' => $filesPerJob,
                        'jobId' => $jobId,
                        'last' => (($i + 1) >= $jobAmount) ? 'true' : ''
                    ]
                ]];
            }
            $zip->close();
        }

        // here we have to use this method and not the JSON action helper ($this->_helper->json()) because this will add
        // Content-Type: application/json which fires a download window in most browsers, because this is a normal POST
        // request and not XHR where the content-type doesn't matter
        $responseJson = $this->encodeJson([
            'success' => true,
            'jobs' => $jobs,
            'jobId' => $jobId
        ]);

        return new Response($responseJson);
    }

    /**
     * @Route("/import-zip-files")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importZipFilesAction(Request $request)
    {
        $jobId = $request->get('jobId');
        $limit = (int)$request->get('limit');
        $offset = (int)$request->get('offset');
        $importAsset = Asset::getById($request->get('parentId'));
        $zipFile = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $jobId . '.zip';
        $tmpDir = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/zip-import';

        if (!is_dir($tmpDir)) {
            File::mkdir($tmpDir, 0777, true);
        }

        $zip = new \ZipArchive;
        if ($zip->open($zipFile) === true) {
            for ($i = $offset; $i < ($offset + $limit); $i++) {
                $path = $zip->getNameIndex($i);

                if ($path !== false) {
                    if ($zip->extractTo($tmpDir . '/', $path)) {
                        $tmpFile = $tmpDir . '/' . preg_replace('@^/@', '', $path);

                        $filename = Element\Service::getValidKey(basename($path), 'asset');

                        $relativePath = '';
                        if (dirname($path) != '.') {
                            $relativePath = dirname($path);
                        }

                        $parentPath = $importAsset->getRealFullPath() . '/' . preg_replace('@^/@', '', $relativePath);
                        $parent = Asset\Service::createFolderByPath($parentPath);

                        // check for duplicate filename
                        $filename = $this->getSafeFilename($parent->getRealFullPath(), $filename);

                        if ($parent->isAllowed('create')) {
                            $asset = Asset::create($parent->getId(), [
                                'filename' => $filename,
                                'sourcePath' => $tmpFile,
                                'userOwner' => $this->getUser()->getId(),
                                'userModification' => $this->getUser()->getId()
                            ]);

                            @unlink($tmpFile);
                        } else {
                            Logger::debug('prevented creating asset because of missing permissions');
                        }
                    }
                }
            }
            $zip->close();
        }

        if ($request->get('last')) {
            unlink($zipFile);
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/import-server")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importServerAction(Request $request)
    {
        $success = true;
        $filesPerJob = 5;
        $jobs = [];
        $importDirectory = str_replace('/fileexplorer', PIMCORE_PROJECT_ROOT, $request->get('serverPath'));
        if (is_dir($importDirectory)) {
            $files = rscandir($importDirectory . '/');
            $count = count($files);
            $jobFiles = [];

            for ($i = 0; $i < $count; $i++) {
                if (is_dir($files[$i])) {
                    continue;
                }

                $jobFiles[] = preg_replace('@^' . preg_quote($importDirectory, '@') . '@', '', $files[$i]);

                if (count($jobFiles) >= $filesPerJob || $i >= ($count - 1)) {
                    $jobs[] = [[
                        'url' => '/admin/asset/import-server-files',
                        'params' => [
                            'parentId' => $request->get('parentId'),
                            'serverPath' => $importDirectory,
                            'files' => implode('::', $jobFiles)
                        ]
                    ]];
                    $jobFiles = [];
                }
            }
        }

        return $this->json([
            'success' => $success,
            'jobs' => $jobs
        ]);
    }

    /**
     * @Route("/import-server-files")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function importServerFilesAction(Request $request)
    {
        $assetFolder = Asset::getById($request->get('parentId'));
        $serverPath = $request->get('serverPath');
        $files = explode('::', $request->get('files'));

        foreach ($files as $file) {
            $absolutePath = $serverPath . $file;
            if (is_file($absolutePath)) {
                $relFolderPath = str_replace('\\', '/', dirname($file));
                $folder = Asset\Service::createFolderByPath($assetFolder->getRealFullPath() . $relFolderPath);
                $filename = basename($file);

                // check for duplicate filename
                $filename = Element\Service::getValidKey($filename, 'asset');
                $filename = $this->getSafeFilename($folder->getRealFullPath(), $filename);

                if ($assetFolder->isAllowed('create')) {
                    $asset = Asset::create($folder->getId(), [
                        'filename' => $filename,
                        'sourcePath' => $absolutePath,
                        'userOwner' => $this->getUser()->getId(),
                        'userModification' => $this->getUser()->getId()
                    ]);
                } else {
                    Logger::debug('prevented creating asset because of missing permissions ');
                }
            }
        }

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * @Route("/import-url")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function importUrlAction(Request $request)
    {
        $success = true;

        $data = Tool::getHttpData($request->get('url'));
        $filename = basename($request->get('url'));
        $parentId = $request->get('id');
        $parentAsset = Asset::getById(intval($parentId));

        $filename = Element\Service::getValidKey($filename, 'asset');
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if (empty($filename)) {
            throw new \Exception('The filename of the asset is empty');
        }

        // check for duplicate filename
        $filename = $this->getSafeFilename($parentAsset->getRealFullPath(), $filename);

        if ($parentAsset->isAllowed('create')) {
            $asset = Asset::create($parentId, [
                'filename' => $filename,
                'data' => $data,
                'userOwner' => $this->getUser()->getId(),
                'userModification' => $this->getUser()->getId()
            ]);
            $success = true;
        } else {
            Logger::debug('prevented creating asset because of missing permissions');
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/clear-thumbnail")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearThumbnailAction(Request $request)
    {
        $success = false;

        if ($asset = Asset::getById($request->get('id'))) {
            if (method_exists($asset, 'clearThumbnails')) {
                if (!$asset->isAllowed('publish')) {
                    throw new \Exception('not allowed to publish');
                }

                $asset->clearThumbnails(true); // force clear
                $asset->save();

                $success = true;
            }
        }

        return $this->json(['success' => $success]);
    }

    /**
     * @Route("/grid-proxy")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function gridProxyAction(Request $request)
    {
        if ($request->get('data')) {
            if ($request->get('xaction') == 'update') {
                //TODO probably not needed
            }
        } else {
            $db = \Pimcore\Db::get();
            // get list of objects
            $folder = Asset::getById($request->get('folderId'));

            $start = 0;
            $limit = 20;
            $orderKey = 'id';
            $order = 'ASC';

            if ($request->get('limit')) {
                $limit = $request->get('limit');
            }
            if ($request->get('start')) {
                $start = $request->get('start');
            }

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $orderKey = $sortingSettings['orderKey'];
                if ($orderKey == 'fullpath') {
                    $orderKey = ['path', 'filename'];
                }

                $order = $sortingSettings['order'];
            }

            $list = new Asset\Listing();

            $conditionFilters = [];
            if ($request->get('only_direct_children') == 'true') {
                $conditionFilters[] = 'parentId = ' . $folder->getId();
            } else {
                $conditionFilters[] = 'path LIKE ' . ($folder->getRealFullPath() == '/' ? "'/%'" : $list->quote($folder->getRealFullPath() . '/%'));
            }

            $conditionFilters[] = "type != 'folder'";
            $filterJson = $request->get('filter');
            if ($filterJson) {
                $filters = $this->decodeJson($filterJson);
                foreach ($filters as $filter) {
                    $operator = '=';

                    $filterField = $filter['property'];
                    $filterOperator = $filter['operator'];
                    $filterType = $filter['type'];

                    if ($filterType == 'string') {
                        $operator = 'LIKE';
                    } elseif ($filterType == 'numeric') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                    } elseif ($filterType == 'date') {
                        if ($filterOperator == 'lt') {
                            $operator = '<';
                        } elseif ($filterOperator == 'gt') {
                            $operator = '>';
                        } elseif ($filterOperator == 'eq') {
                            $operator = '=';
                        }
                        $filter['value'] = strtotime($filter['value']);
                    } elseif ($filterType == 'list') {
                        $operator = '=';
                    } elseif ($filterType == 'boolean') {
                        $operator = '=';
                        $filter['value'] = (int) $filter['value'];
                    }
                    // system field
                    $value = $filter['value'];
                    if ($operator == 'LIKE') {
                        $value = '%' . $value . '%';
                    }

                    $field = '`' . $filterField . '` ';
                    if ($filterField == 'fullpath') {
                        $field = 'CONCAT(path,filename)';
                    }

                    $conditionFilters[] =  $field . $operator . ' ' . $db->quote($value);
                }
            }

            if (!$this->getUser()->isAdmin()) {
                $userIds = $this->getUser()->getRoles();
                $userIds[] = $this->getUser()->getId();
                $conditionFilters[] .= ' (
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(CONCAT(path, filename),cpath)=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                    OR
                                                    (select list from users_workspaces_asset where userId in (' . implode(',', $userIds) . ') and LOCATE(cpath,CONCAT(path, filename))=1  ORDER BY LENGTH(cpath) DESC LIMIT 1)=1
                                                 )';
            }

            $condition = implode(' AND ', $conditionFilters);
            $list->setCondition($condition);
            $list->setLimit($limit);
            $list->setOffset($start);
            $list->setOrder($order);
            $list->setOrderKey($orderKey);

            $list->load();

            $assets = [];
            foreach ($list->getAssets() as $asset) {

                /** @var $asset Asset */
                $filename = PIMCORE_ASSET_DIRECTORY . '/' . $asset->getRealFullPath();
                $size = @filesize($filename);

                $assets[] = [
                    'id' => $asset->getid(),
                    'type' => $asset->getType(),
                    'fullpath' => $asset->getRealFullPath(),
                    'creationDate' => $asset->getCreationDate(),
                    'modificationDate' => $asset->getModificationDate(),
                    'size' => formatBytes($size),
                    'idPath' => $data['idPath'] = Element\Service::getIdPath($asset)
                ];
            }

            return $this->json(['data' => $assets, 'success' => true, 'total' => $list->getTotalCount()]);
        }
    }

    /**
     * @Route("/get-text")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getTextAction(Request $request)
    {
        $asset = Asset::getById($request->get('id'));

        if (!$asset->isAllowed('view')) {
            throw new \Exception('not allowed to view');
        }

        $page = $request->get('page');
        if ($asset instanceof Asset\Document) {
            $text = $asset->getText($page);
        }

        return $this->json(['success' => 'true', 'text' => $text]);
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $isMasterRequest = $event->isMasterRequest();
        if (!$isMasterRequest) {
            return;
        }

        $this->checkActionPermission($event, 'assets', [
            'getImageThumbnailAction', 'getVideoThumbnailAction', 'getDocumentThumbnailAction'
        ]);

        $this->_assetService = new Asset\Service($this->getUser());
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        // nothing to do
    }
}
