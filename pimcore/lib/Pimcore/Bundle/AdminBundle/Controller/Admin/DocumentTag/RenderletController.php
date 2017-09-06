<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DocumentTag;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Pimcore\Templating\Model\ViewModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RenderletController extends AdminController
{
    /**
     * Handles editmode preview for renderlets
     *
     * @Route("/document_tag/renderlet")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function renderletAction(Request $request)
    {
        $query      = $request->query->all();
        $attributes = [];

        // load element to make sure the request is valid
        $this->loadElement($request);

        $actionRenderer = $this->get('pimcore.templating.action_renderer');
        $tagHandler     = $this->get('pimcore.document.tag.handler');

        $controller = $request->get('controller');
        $action     = $request->get('action');

        $moduleOrBundle = null;
        if ($request->get('bundle')) {
            $moduleOrBundle = $request->get('bundle');
        } elseif ($request->get('module')) {
            $moduleOrBundle = $request->get('bundle');
        }

        // set document if set in request
        if ($document = $request->get('pimcore_parentDocument')) {
            $document = Document::getById($document);
            if ($document) {
                $attributes = $actionRenderer->addDocumentAttributes($document, $attributes);
            }
        }

        // override template if set
        if ($template = $request->get('template')) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $template;
        }

        foreach (['controller', 'action', 'module', 'bundle'] as $key) {
            if (isset($query[$key])) {
                unset($query[$key]);
            }
        }

        // setting locale manually here before rendering the action to make sure editables use the right locale - if this
        // is needed in multiple places, move this to the tag handler instead (see #1834)
        if ($attributes['_locale']) {
            $this->get('pimcore.locale')->setLocale($attributes['_locale']);
        }

        $result = $tagHandler->renderAction(new ViewModel(), $controller, $action, $moduleOrBundle, $attributes, $query);

        return new Response($result);
    }

    private function loadElement(Request $request): ElementInterface
    {
        $element = null;

        $id   = $request->get('id');
        $type = $request->get('type');

        if ($id && $type) {
            $element = Service::getElementById($type, (int)$id);
        }

        if (!$element instanceof ElementInterface) {
            throw $this->createNotFoundException(sprintf('Element with type %s and ID %d was not found', $type ?: 'null', $id ?: 'null'));
        }

        if ($element instanceof AbstractElement) {
            if (!$element->isAllowed('view')) {
                throw $this->createAccessDeniedException(sprintf('Access to element with type %s and ID %d is not allowed', $type, $id));
            }
        }

        return $element;
    }
}
