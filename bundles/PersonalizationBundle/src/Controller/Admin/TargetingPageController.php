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
use Pimcore\Bundle\PersonalizationBundle\Model\Tool\Targeting\TargetGroup;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Pimcore\Model\Document;

/**
 * @Route("/targeting")
 *
 * @internal
 */
class TargetingPageController extends PageController
{

     /**
      * @Route("/clear-targeting-editable-data", name="pimcore_bundle_personalization_clear_targeting_editable_data", methods={"PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function clearTargetingEditableDataAction (Request $request): JsonResponse
    {
        $targetGroupId = $request->request->getInt('targetGroup');
        $docId = $request->request->getInt ('id');

        $doc = Document\PageSnippet::getById ($docId);

        if (!$doc) {
            throw $this->createNotFoundException ('Document not found');
        }

        foreach ($doc->getEditables () as $editable) {

            if ($targetGroupId && $doc instanceof TargetingDocumentInterface) {

                // remove target group specific elements
                if (preg_match ('/^' . preg_quote ($doc->getTargetGroupEditablePrefix ($targetGroupId), '/') . '/', $editable->getName ())) {
                    $doc->removeEditable ($editable->getName ());
                }
            }
        }

        $this->saveToSession ($doc, true);

        return $this->adminJson ([
            'success' => true,
        ]);
    }

    public function configureElementTargeting (Request $request, ElementInterface $element): void
    {
        if (!$element instanceof TargetingDocumentInterface) {
            return;
        }

        // set selected target group on element
        if ($request->get ('_ptg')) {
            $targetGroup = TargetGroup::getById ((int)$request->get ('_ptg'));
            if ($targetGroup) {
                $element->setUseTargetGroup ($targetGroup->getId ());
            }
        }
    }


}
