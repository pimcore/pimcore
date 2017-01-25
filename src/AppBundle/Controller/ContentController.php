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
     * @param array $templateVars
     * @return array
     */
    public function portalAction(Request $request, array $templateVars)
    {
        $templateVars = $this->defaultAction($request, $templateVars);
        $templateVars['isPortal'] = true;

        return $templateVars;
    }

    /**
     * @Template("AppBundle:Content:content.html.twig")
     *
     * @param Request $request
     * @param array $templateVars
     * @return array
     */
    public function defaultAction(Request $request, array $templateVars)
    {
        /** @var Document $document */
        $document = $templateVars['document'];

        if ($request->get('debugDocument')) {
            dump($document);
        }

        $mainNavStartNode = $document->getProperty("mainNavStartNode");
        if (!$mainNavStartNode) {
            $mainNavStartNode = Document::getById(1);
        }

        /** @var ZendViewHelperBridge $bridge */
        $bridge = $this->container->get('pimcore.view.zend_view_helper_bridge');

        $mainNavigation = $bridge->execute('pimcoreNavigation', [$document, $mainNavStartNode]);

        $templateVars['mainNavigation']   = $mainNavigation;
        $templateVars['mainNavStartNode'] = $mainNavStartNode;

        $hideLeftNav = $templateVars['hideLeftNav'] = $document->getProperty('leftNavHide');
        if (!$hideLeftNav) {
            $leftNavStartNode = $document->getProperty('leftNavStartNode');
            if (!$leftNavStartNode) {
                $leftNavStartNode = $mainNavStartNode;
            }

            $leftNavigation = $bridge->execute('pimcoreNavigation', [$document, $leftNavStartNode]);

            $templateVars['leftNavigation']   = $leftNavigation;
            $templateVars['leftNavStartNode'] = $leftNavStartNode;
        }

        $languageSwitcher = $this->container->get('app.templating.language_switcher');
        $templateVars['language_links'] = $languageSwitcher->getLocalizedLinks($document);

        $templateVars['isPortal'] = false;

        return $templateVars;
    }

    /**
     * @Template("AppBundle:Content:thumbnails.html.twig")
     *
     * @param Request $request
     * @param array $templateVars
     * @return array
     */
    public function thumbnailsAction(Request $request, array $templateVars)
    {
        $templateVars = $this->defaultAction($request, $templateVars);

        // this is just used for demonstration
        $templateVars['image'] = Asset::getById(53);

        return $templateVars;
    }

    /**
     * @Template("AppBundle:Content:website-translations.html.twig")
     *
     * @param Request $request
     * @param array $templateVars
     * @return array
     */
    public function websiteTranslationsAction(Request $request, array $templateVars)
    {
        return $this->defaultAction($request, $templateVars);
    }

    /**
     * @Template("AppBundle:Content:editable-roundup.html.twig")
     *
     * @param Request $request
     * @param array $templateVars
     * @return array
     */
    public function editableRoundupAction(Request $request, array $templateVars)
    {
        return $this->defaultAction($request, $templateVars);
    }
}
