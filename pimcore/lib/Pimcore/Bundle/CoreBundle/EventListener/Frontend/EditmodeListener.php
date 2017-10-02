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
use Pimcore\Config;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Model\User;
use Pimcore\Version;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
     * @var array
     */
    protected $contentTypes = [
        'text/html'
    ];

    /**
     * @param EditmodeResolver $editmodeResolver
     * @param DocumentResolver $documentResolver
     * @param UserLoader $userLoader
     * @param PimcoreBundleManager $bundleManager
     */
    public function __construct(
        EditmodeResolver $editmodeResolver,
        DocumentResolver $documentResolver,
        UserLoader $userLoader,
        PimcoreBundleManager $bundleManager
    ) {
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
        $this->userLoader = $userLoader;
        $this->bundleManager = $bundleManager;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse'
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

        // trigger this once to make sure it is resolved properly (and set for legacy)
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
            'request' => $request->getPathInfo()
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
                $startupJavascript = '/pimcore/static6/js/pimcore/document/edit/startup.js';

                $headHtml = $this->buildHeadHtml($document, $user->getLanguage());
                $bodyHtml = "\n\n" . '<script type="text/javascript" src="' . $startupJavascript . '?_dc=' . Version::$revision . '"></script>' . "\n\n";

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
     * @param User $user
     * @param string $language
     *
     * @return string
     */
    protected function buildHeadHtml(Document $document, $language)
    {
        $config = Config::getSystemConfig();
        $libraries = $this->getEditmodeLibraries();
        $scripts = $this->getEditmodeScripts();
        $stylesheets = $this->getEditmodeStylesheets();

        $headHtml = "\n\n\n<!-- pimcore editmode -->\n";
        $headHtml .= '<meta name="google" value="notranslate">';
        $headHtml .= "\n\n";

        // include stylesheets
        foreach ($stylesheets as $stylesheet) {
            $headHtml .= '<link rel="stylesheet" type="text/css" href="' . $stylesheet . '?_dc=' . Version::$revision . '" />';
            $headHtml .= "\n";
        }

        $headHtml .= "\n\n";
        $headHtml .= '<script type="text/javascript">var jQueryPreviouslyLoaded = (typeof jQuery == "undefined") ? false : true;</script>' . "\n";

        // include script libraries
        foreach ($libraries as $script) {
            $headHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
            $headHtml .= "\n";
        }

        // combine the pimcore scripts in non-devmode
        if ($config->general->devmode) {
            foreach ($scripts as $script) {
                $headHtml .= '<script type="text/javascript" src="' . $script . '?_dc=' . Version::$revision . '"></script>';
                $headHtml .= "\n";
            }
        } else {
            $scriptContents = '';
            foreach ($scripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            }

            $headHtml .= '<script type="text/javascript" src="' . \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents) . '"></script>' . "\n";
        }

        $headHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-system?language=' . $language . '&_dc=' . Version::$revision . '"></script>' . "\n";
        $headHtml .= "\n\n";

        // set var for editable configurations which is filled by Document\Tag::admin()
        $headHtml .= '<script type="text/javascript">
            var editableConfigurations = new Array();
            var pimcore_document_id = ' . $document->getId() . ';

            if(jQueryPreviouslyLoaded) {
                jQuery.noConflict( true );
            }
        </script>';

        $headHtml .= "\n\n<!-- /pimcore editmode -->\n\n\n";

        return $headHtml;
    }

    /**
     * @return array
     */
    protected function getEditmodeLibraries()
    {
        return [
            '/pimcore/static6/js/pimcore/common.js',
            '/pimcore/static6/js/lib/prototype-light.js',
            '/pimcore/static6/js/lib/jquery.min.js',
            '/pimcore/static6/js/lib/ext/ext-all' . (PIMCORE_DEVMODE ? '-debug' : '') . '.js',
            '/pimcore/static6/js/lib/ckeditor/ckeditor.js'
        ];
    }

    /**
     * @return array
     */
    protected function getEditmodeScripts()
    {
        return array_merge(
            [
                '/pimcore/static6/js/pimcore/functions.js',
                '/pimcore/static6/js/pimcore/overrides.js',
                '/pimcore/static6/js/pimcore/element/tag/imagehotspotmarkereditor.js',
                '/pimcore/static6/js/pimcore/element/tag/imagecropper.js',
                '/pimcore/static6/js/pimcore/document/edit/helper.js',
                '/pimcore/static6/js/pimcore/elementservice.js',
                '/pimcore/static6/js/pimcore/document/edit/dnd.js',
                '/pimcore/static6/js/pimcore/document/tag.js',
                '/pimcore/static6/js/pimcore/document/tags/block.js',
                '/pimcore/static6/js/pimcore/document/tags/date.js',
                '/pimcore/static6/js/pimcore/document/tags/href.js',
                '/pimcore/static6/js/pimcore/document/tags/multihref.js',
                '/pimcore/static6/js/pimcore/document/tags/checkbox.js',
                '/pimcore/static6/js/pimcore/document/tags/image.js',
                '/pimcore/static6/js/pimcore/document/tags/input.js',
                '/pimcore/static6/js/pimcore/document/tags/link.js',
                '/pimcore/static6/js/pimcore/document/tags/select.js',
                '/pimcore/static6/js/pimcore/document/tags/snippet.js',
                '/pimcore/static6/js/pimcore/document/tags/textarea.js',
                '/pimcore/static6/js/pimcore/document/tags/numeric.js',
                '/pimcore/static6/js/pimcore/document/tags/wysiwyg.js',
                '/pimcore/static6/js/pimcore/document/tags/renderlet.js',
                '/pimcore/static6/js/pimcore/document/tags/table.js',
                '/pimcore/static6/js/pimcore/document/tags/video.js',
                '/pimcore/static6/js/pimcore/document/tags/multiselect.js',
                '/pimcore/static6/js/pimcore/document/tags/areablock.js',
                '/pimcore/static6/js/pimcore/document/tags/area.js',
                '/pimcore/static6/js/pimcore/document/tags/pdf.js',
                '/pimcore/static6/js/pimcore/document/tags/embed.js',
                '/pimcore/static6/js/pimcore/document/edit/helper.js'
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
                '/pimcore/static6/css/icons.css',
                '/pimcore/static6/css/editmode.css?_dc=' . time()
            ],
            $this->bundleManager->getEditmodeCssPaths()
        );
    }
}
