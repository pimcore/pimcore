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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore\Bundle\AdminBundle\Security\User\UserLoader;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Version;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Asset\Packages;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Modifies responses for editmode
 */
class EditmodeListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentResolver
     */
    protected $documentResolver;

    /**
     * @var UserLoader
     */
    protected $userLoader;

    /**
     * @var PimcoreBundleManager
     */
    protected $bundleManager;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Packages
     */
    protected $package;

    /**
     * @var array
     */
    protected $contentTypes = [
        'text/html',
    ];

    /**
     * @param EditmodeResolver $editmodeResolver
     * @param DocumentResolver $documentResolver
     * @param UserLoader $userLoader
     * @param PimcoreBundleManager $bundleManager
     * @param RouterInterface $router
     * @param Packages $package
     */
    public function __construct(
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver,
        UserLoader $userLoader,
        PimcoreBundleManager $bundleManager,
        RouterInterface $router,
        Packages $package
    ) {
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
        $this->userLoader = $userLoader;
        $this->bundleManager = $bundleManager;
        $this->router = $router;
        $this->package = $package;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$event->isMasterRequest()) {
            return; // only resolve editmode in frontend
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // trigger this once to make sure it is resolved properly
        // TODO is this needed?
        $this->editmodeResolver->isEditmode($request);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$event->isMasterRequest()) {
            return; // only master requests inject editmode assets
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        if (!$this->editmodeResolver->isEditmode($request)) {
            return;
        }

        if (!$this->contentTypeMatches($response)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $this->logger->info('Injecting editmode assets into request {request}', [
            'request' => $request->getPathInfo(),
        ]);

        $this->addEditmodeAssets($document, $response);

        // set sameorigin header for editmode responses
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', true);
    }

    /**
     * @param Response $response
     *
     * @return bool
     */
    protected function contentTypeMatches(Response $response)
    {
        $contentType = $response->headers->get('Content-Type');
        if (!$contentType) {
            return true;
        }

        // check for substring as the content type could define attributes (e.g. charset)
        foreach ($this->contentTypes as $ct) {
            if (false !== strpos($contentType, $ct)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inject editmode assets into response HTML
     *
     * @param Document $document
     * @param Response $response
     */
    protected function addEditmodeAssets(Document $document, Response $response)
    {
        if (Document\Service::isValidType($document->getType())) {
            $html = $response->getContent();

            if (!$html) {
                return;
            }

            $user = $this->userLoader->getUser();

            $htmlElement = preg_match('/<html[^a-zA-Z]?( [^>]+)?>/', $html);
            $headElement = preg_match('/<head[^a-zA-Z]?( [^>]+)?>/', $html);
            $bodyElement = preg_match('/<body[^a-zA-Z]?( [^>]+)?>/', $html);

            $skipCheck = false;

            // if there's no head and no body, create a wrapper including these elements
            // add html headers for snippets in editmode, so there is no problem with javascript
            if (!$headElement && !$bodyElement && !$htmlElement) {
                $html = "<!DOCTYPE html>\n<html>\n<head></head><body>" . $html . '</body></html>';
                $skipCheck = true;
            }

            if ($skipCheck || ($headElement && $bodyElement && $htmlElement)) {
                $startupJavascript = '/bundles/pimcoreadmin/js/pimcore/document/edit/startup.js';

                $headHtml = $this->buildHeadHtml($document, $user->getLanguage());
                $bodyHtml = "\n\n" . '<script src="' . $startupJavascript . '?_dc=' . Version::getRevision() . '"></script>' . "\n\n";

                $html = preg_replace('@</head>@i', $headHtml . "\n\n</head>", $html, 1);
                $html = preg_replace('@</body>@i', $bodyHtml . "\n\n</body>", $html, 1);

                $response->setContent($html);
            } else {
                $response->setContent('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
            }
        }
    }

    /**
     * @param Document $document
     * @param string $language
     *
     * @return string
     */
    protected function buildHeadHtml(Document $document, $language)
    {
        $libraries = $this->getEditmodeLibraries();
        $scripts = $this->getEditmodeScripts();
        $stylesheets = $this->getEditmodeStylesheets();

        $headHtml = "\n\n\n<!-- pimcore editmode -->\n";
        $headHtml .= '<meta name="google" value="notranslate">';
        $headHtml .= "\n\n";

        // include stylesheets
        foreach ($stylesheets as $stylesheet) {
            $headHtml .= '<link rel="stylesheet" type="text/css" href="' . $stylesheet . '?_dc=' . Version::getRevision() . '" />';
            $headHtml .= "\n";
        }

        $headHtml .= "\n\n";

        // include script libraries
        foreach ($libraries as $script) {
            $headHtml .= '<script src="' . $script . '?_dc=' . Version::getRevision() . '"></script>';
            $headHtml .= "\n";
        }

        // combine the pimcore scripts in non-devmode
        if (\Pimcore::disableMinifyJs()) {
            foreach ($scripts as $script) {
                $headHtml .= '<script src="' . $script . '?_dc=' . Version::getRevision() . '"></script>';
                $headHtml .= "\n";
            }
        } else {
            $scriptContents = '';
            foreach ($scripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            }

            $headHtml .= '<script src="' . $this->router->generate('pimcore_admin_misc_scriptproxy', \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents, false)) . '"></script>' . "\n";
        }
        $path = $this->router->generate('pimcore_admin_misc_jsontranslationssystem', [
            'language' => $language,
            '_dc' => Version::getRevision(),
        ]);

        $headHtml .= '<script src="'.$path.'"></script>' . "\n";
        $headHtml .= '<script src="' . $this->router->generate('fos_js_routing_js', ['callback' => 'fos.Router.setData']) . '"></script>' . "\n";
        $headHtml .= "\n\n";

        // set var for editable configurations which is filled by Document\Tag::admin()
        $headHtml .= '<script>
            var editableDefinitions = [];
            var pimcore_document_id = ' . $document->getId() . ';
        </script>';

        $headHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";

        return $headHtml;
    }

    /**
     * @return array
     */
    protected function getEditmodeLibraries()
    {
        $disableMinifyJs = \Pimcore::disableMinifyJs();

        return [
            '/bundles/pimcoreadmin/js/pimcore/common.js',
            '/bundles/pimcoreadmin/js/lib/class.js',
            '/bundles/pimcoreadmin/js/lib/ext/ext-all' . ($disableMinifyJs ? '-debug' : '') . '.js',
            '/bundles/pimcoreadmin/js/lib/ckeditor/ckeditor.js',
        ];
    }

    /**
     * @return array
     */
    protected function getEditmodeScripts()
    {
        return array_merge(
            [
                '/bundles/fosjsrouting/js/router.js',
                '/bundles/pimcoreadmin/js/pimcore/functions.js',
                '/bundles/pimcoreadmin/js/pimcore/overrides.js',
                '/bundles/pimcoreadmin/js/pimcore/tool/milestoneslider.js',
                '/bundles/pimcoreadmin/js/pimcore/element/tag/imagehotspotmarkereditor.js',
                '/bundles/pimcoreadmin/js/pimcore/element/tag/imagecropper.js',
                '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',
                '/bundles/pimcoreadmin/js/pimcore/elementservice.js',
                '/bundles/pimcoreadmin/js/pimcore/document/edit/dnd.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editable.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/block.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/scheduledblock.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/date.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/relation.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/relations.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/checkbox.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/image.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/input.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/link.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/select.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/snippet.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/textarea.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/numeric.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/wysiwyg.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/renderlet.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/table.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/video.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/multiselect.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/areablock.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/area.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/pdf.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/embed.js',
                '/bundles/pimcoreadmin/js/pimcore/document/tags/compatibility-layer.js', //@TODO Remove deprecated tag aliases in Pimcore 7.
                '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',
            ],
            $this->bundleManager->getEditmodeJsPaths()
        );
    }

    /**
     * @return array
     */
    protected function getEditmodeStylesheets()
    {
        return array_merge(
            [
                '/bundles/pimcoreadmin/css/icons.css',
                '/bundles/pimcoreadmin/css/editmode.css?_dc=' . time(),
            ],
            $this->bundleManager->getEditmodeCssPaths()
        );
    }
}
