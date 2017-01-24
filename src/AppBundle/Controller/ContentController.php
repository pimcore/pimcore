<?php

namespace AppBundle\Controller;

use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use PimcoreBundle\View\ZendViewHelperBridge;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ContentController extends Controller
{
    /**
     * @Template("AppBundle:Test:php.html.php", engine="php")
     *
     * @param Request $request
     * @return array
     */
    public function phpAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @Template("AppBundle:Test:twig.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function twigAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @Template("AppBundle:Content:portal.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function portalAction(Request $request)
    {
        $vars = $this->defaultAction($request);
        $vars['isPortal'] = true;

        return $vars;
    }

    /**
     * @Template("AppBundle:Content:content.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function defaultAction(Request $request)
    {
        $vars = $this->resolveContent($request);

        /** @var Document $document */
        $document = $vars['document'];

        $mainNavStartNode = $document->getProperty("mainNavStartNode");
        if (!$mainNavStartNode) {
            $mainNavStartNode = Document::getById(1);
        }

        /** @var ZendViewHelperBridge $bridge */
        $bridge = $this->container->get('pimcore.view.zend_view_helper_bridge');

        $mainNavigation = $bridge->execute('pimcoreNavigation', [$document, $mainNavStartNode]);

        $vars['mainNavigation']   = $mainNavigation;
        $vars['mainNavStartNode'] = $mainNavStartNode;

        $hideLeftNav = $vars['hideLeftNav'] = $document->getProperty('leftNavHide');
        if (!$hideLeftNav) {
            $leftNavStartNode = $document->getProperty('leftNavStartNode');
            if (!$leftNavStartNode) {
                $leftNavStartNode = $mainNavStartNode;
            }

            $leftNavigation = $bridge->execute('pimcoreNavigation', [$document, $leftNavStartNode]);

            $vars['leftNavigation']   = $leftNavigation;
            $vars['leftNavStartNode'] = $leftNavStartNode;
        }

        $languageSwitcher = $this->container->get('app.templating.language_switcher');
        $vars['language_links'] = $languageSwitcher->getLocalizedLinks($document);

        $vars['isPortal'] = false;

        // TODO make this global somewhere
        $vars['editmode'] = false;
        if ($request->get('pimcore_editmode')) {
            $vars['editmode'] = true;
        }

        return $vars;
    }

    /**
     * @Template("AppBundle:Content:thumbnails.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function thumbnailsAction(Request $request)
    {
        $vars = $this->defaultAction($request);

        // this is just used for demonstration
        $vars['image'] = Asset::getById(53);

        return $vars;
    }

    /**
     * @Template("AppBundle:Content:website-translations.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function websiteTranslationsAction(Request $request)
    {
        return $this->defaultAction($request);
    }

    /**
     * @Template("AppBundle:Content:editable-roundup.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function editableRoundupAction(Request $request)
    {
        return $this->defaultAction($request);
    }

    /**
     * @param Request $request
     * @return array
     */
    protected function resolveContent(Request $request)
    {
        $document = $this
            ->get('pimcore.service.request.document_resolver')
            ->getDocument($request);

        if ($request->get('debugDocument')) {
            dump($document);
        }

        return [
            'document' => $document
        ];
    }
}
