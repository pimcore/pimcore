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

namespace Pimcore\Bundle\PersonalizationBundle\Controller\Admin;

use Pimcore\Bundle\AdminBundle\Controller\Admin\Document\PageController;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Document\StaticPageGenerator;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/targeting/page")
 *
 * @internal
 */
class TargetingPageController extends PageController
{
    /**
     * @Route("/clear-targeting-editable-data", name="pimcore_bundle_personalization_clear_targeting_page_editable_data", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearTargetingEditableDataAction(Request $request): JsonResponse
    {
        $targetGroupId = $request->request->getInt('targetGroup');
        $docId = $request->request->getInt('id');

        $doc = Document\PageSnippet::getById($docId);

        if (!$doc) {
            throw $this->createNotFoundException('Document not found');
        }

        foreach ($doc->getEditables() as $editable) {
            if ($targetGroupId && $doc instanceof TargetingDocumentInterface) {
                // remove target group specific elements
                if (preg_match('/^' . preg_quote($doc->getTargetGroupEditablePrefix($targetGroupId), '/') . '/', $editable->getName())) {
                    $doc->removeEditable($editable->getName());
                }
            }
        }

        $this->saveToSession($doc, $request->getSession(), true);

        return $this->adminJson([
            'success' => true,
        ]);
    }

    /**
     * @Route("/save", name="pimcore_admin_document_page_save", methods={"PUT", "POST"})
     *
     * @param Request $request
     * @param StaticPageGenerator $staticPageGenerator
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function saveAction(Request $request, StaticPageGenerator $staticPageGenerator): JsonResponse
    {
        return parent::saveAction($request, $staticPageGenerator);
    }

    protected function addDataToDocument(Request $request, Document $document): void
    {
        if ($document instanceof Document\PageSnippet) {
            // if a target group variant get's saved, we have to load all other editables first, otherwise they will get deleted

            if ($request->get('appendEditables')
                || ($document instanceof TargetingDocumentInterface)) { // ensure editable are loaded
                $document->getEditables();
            } else {
                // ensure no editables (e.g. from session, version, ...) are still referenced
                $document->setEditables(null);
            }

            if ($request->get('data')) {
                $data = $this->decodeJson($request->get('data'));
                foreach ($data as $name => $value) {
                    $data = $value['data'] ?? null;
                    $type = $value['type'];
                    $document->setRawEditable($name, $type, $data);
                }
            }
        }
    }
}
