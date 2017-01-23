<?php

namespace AppBundle\Controller;

use Pimcore\Model\Document;
use PimcoreBundle\View\ZendViewHelperBridge;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class ContentController extends Controller
{
    /**
     * @Template("AppBundle:Content:php.html.php", engine="php")
     *
     * @param Request $request
     * @return array
     */
    public function phpAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @Template("AppBundle:Content:twig.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function twigAction(Request $request)
    {
        return $this->resolveContent($request);
    }

    /**
     * @Template("AppBundle:Content:full-content.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function portalAction(Request $request)
    {
        $vars = $this->fullContentAction($request);
        $vars['isPortal'] = true;

        return $vars;
    }

    /**
     * @Template("AppBundle:Content:full-content.html.twig")
     *
     * @param Request $request
     * @return array
     */
    public function fullContentAction(Request $request)
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
     * @param Request $request
     * @return array
     */
    protected function resolveContent(Request $request)
    {
        $document = $request->get('contentDocument');

        if ($request->get('debugDocument')) {
            dump($document);
        }

        return [
            'document' => $document
        ];
    }
}
