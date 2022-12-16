<?php

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

namespace Pimcore\Bundle\SeoBundle\Controller\Admin\Document;



use Pimcore\Bundle\AdminBundle\Controller\Admin\ElementControllerBase;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Routing\Dynamic\DocumentRouteHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/document")
 *
 * @internal
 */
class DocumentController extends ElementControllerBase
{
    final const DOCUMENT_ROOT_ID = 1;

    /**
     * @Route("/seopanel-tree-root", name="pimcore_seo_document_document_seopaneltreeroot", methods={"GET"})
     *
     * @param DocumentRouteHandler $documentRouteHandler
     *
     * @return JsonResponse
     */
    public function seopanelTreeRootAction(DocumentRouteHandler $documentRouteHandler): JsonResponse
    {
        $this->checkPermission('seo_document_editor');

        /** @var Page $root */
        $root = Page::getById(self::DOCUMENT_ROOT_ID);
        if ($root->isAllowed('list')) {
            // make sure document routes are also built for unpublished documents
            $documentRouteHandler->setForceHandleUnpublishedDocuments(true);

            $nodeConfig = $this->getSeoNodeConfig($root);

            return $this->adminJson($nodeConfig);
        }

        throw $this->createAccessDeniedHttpException();
    }

    private function getSeoNodeConfig(Document $document): array
    {
        $nodeConfig = $this->getTreeNodeConfig($document);

        if ($document instanceof Document\Page) {
            // analyze content
            $nodeConfig['prettyUrl'] = $document->getPrettyUrl();

            $title = $document->getTitle();
            $description = $document->getDescription();

            $nodeConfig['title'] = $title;
            $nodeConfig['description'] = $description;

            $nodeConfig['title_length'] = mb_strlen($title);
            $nodeConfig['description_length'] = mb_strlen($description);
        }

        return $nodeConfig;
    }
}
