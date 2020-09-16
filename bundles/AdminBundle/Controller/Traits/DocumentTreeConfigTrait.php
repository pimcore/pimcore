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

namespace Pimcore\Bundle\AdminBundle\Controller\Traits;

use Pimcore\Config;
use Pimcore\Event\Admin\ElementAdminStyleEvent;
use Pimcore\Model\Document;
use Pimcore\Model\Site;
use Pimcore\Tool\Frontend;

trait DocumentTreeConfigTrait
{
    use AdminStyleTrait;

    /**
     * @param Document $element
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getTreeNodeConfig($element)
    {
        $site = null;
        $childDocument = $element;
        $container = \Pimcore::getContainer();

        /** @var Config $config */
        $config = $container->get(Config::class);

        $tmpDocument = [
            'id' => $childDocument->getId(),
            'idx' => intval($childDocument->getIndex()),
            'text' => $childDocument->getKey(),
            'type' => $childDocument->getType(),
            'path' => $childDocument->getRealFullPath(),
            'basePath' => $childDocument->getRealPath(),
            'locked' => $childDocument->isLocked(),
            'lockOwner' => $childDocument->getLocked() ? true : false,
            'published' => $childDocument->isPublished(),
            'elementType' => 'document',
            'leaf' => true,
            'permissions' => [
                'view' => $childDocument->isAllowed('view'),
                'remove' => $childDocument->isAllowed('delete'),
                'settings' => $childDocument->isAllowed('settings'),
                'rename' => $childDocument->isAllowed('rename'),
                'publish' => $childDocument->isAllowed('publish'),
                'unpublish' => $childDocument->isAllowed('unpublish'),
                'create' => $childDocument->isAllowed('create'),
            ],
        ];

        // add icon
        $tmpDocument['expandable'] = $childDocument->hasChildren();
        $tmpDocument['loaded'] = !$childDocument->hasChildren();

        // set type specific settings
        if ($childDocument->getType() == 'page') {
            $tmpDocument['leaf'] = false;
            $tmpDocument['expanded'] = !$childDocument->hasChildren();

            // test for a site
            if ($site = Site::getByRootId($childDocument->getId())) {
                unset($site->rootDocument);
                $tmpDocument['site'] = $site;
            }
        } elseif ($childDocument->getType() == 'folder' || $childDocument->getType() == 'link' || $childDocument->getType() == 'hardlink') {
            $tmpDocument['leaf'] = false;
            $tmpDocument['expanded'] = !$childDocument->hasChildren();
        } elseif (method_exists($childDocument, 'getTreeNodeConfig')) {
            $tmp = $childDocument->getTreeNodeConfig();
            $tmpDocument = array_merge($tmpDocument, $tmp);
        }

        $this->addAdminStyle($childDocument, ElementAdminStyleEvent::CONTEXT_TREE, $tmpDocument);

        // PREVIEWS temporary disabled, need's to be optimized some time
        if ($childDocument instanceof Document\Page && isset($config['documents']['generate_preview'])) {
            $thumbnailFile = $childDocument->getPreviewImageFilesystemPath();
            // only if the thumbnail exists and isn't out of time
            if (file_exists($thumbnailFile) && filemtime($thumbnailFile) > ($childDocument->getModificationDate() - 20)) {
                $tmpDocument['thumbnail'] = $this->generateUrl('pimcore_admin_page_display_preview_image', ['id' => $childDocument->getId()]);
                $thumbnailFileHdpi = $childDocument->getPreviewImageFilesystemPath(true);
                if (file_exists($thumbnailFileHdpi)) {
                    $tmpDocument['thumbnailHdpi'] = $this->generateUrl('pimcore_admin_page_display_preview_image',
                        ['id' => $childDocument->getId(), 'hdpi' => true]);
                }
            }
        }

        $tmpDocument['cls'] = '';

        if ($childDocument instanceof Document\Page) {
            $tmpDocument['url'] = $childDocument->getFullPath();
            $site = Frontend::getSiteForDocument($childDocument);
            if ($site instanceof Site) {
                $tmpDocument['url'] = 'http://' . $site->getMainDomain() . preg_replace('@^' . $site->getRootPath() . '/?@', '/', $childDocument->getRealFullPath());
            }
        }

        if ($childDocument->getProperty('navigation_exclude')) {
            $tmpDocument['cls'] .= 'pimcore_navigation_exclude ';
        }

        if (!$childDocument->isPublished()) {
            $tmpDocument['cls'] .= 'pimcore_unpublished ';
        }

        if ($childDocument->isLocked()) {
            $tmpDocument['cls'] .= 'pimcore_treenode_locked ';
        }
        if ($childDocument->getLocked()) {
            $tmpDocument['cls'] .= 'pimcore_treenode_lockOwner ';
        }

        return $tmpDocument;
    }
}
