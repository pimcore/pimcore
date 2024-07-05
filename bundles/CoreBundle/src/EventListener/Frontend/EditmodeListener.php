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

namespace Pimcore\Bundle\CoreBundle\EventListener\Frontend;

use Pimcore;
use Pimcore\Bundle\CoreBundle\EventListener\Traits\PimcoreContextAwareTrait;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Extension\Bundle\PimcoreBundleManager;
use Pimcore\Http\Request\Resolver\DocumentResolver;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Model\Document;
use Pimcore\Security\User\UserLoader;
use Pimcore\Version;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

/**
 * Modifies responses for editmode
 *
 * @internal
 */
class EditmodeListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use PimcoreContextAwareTrait;

    protected array $contentTypes = [
        'text/html',
    ];

    public function __construct(
        protected EditmodeResolver $editmodeResolver,
        protected DocumentResolver $documentResolver,
        protected UserLoader $userLoader,
        protected PimcoreBundleManager $bundleManager,
        protected RouterInterface $router,
        private EditmodeEditableDefinitionCollector $editableConfigCollector
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return; // only resolve editmode in frontend
        }

        if (!$this->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return;
        }

        // trigger this once to make sure it is resolved properly
        // TODO is this needed?
        $this->editmodeResolver->isEditmode($request);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$event->isMainRequest()) {
            return; // only main requests inject editmode assets
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

    protected function contentTypeMatches(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        if (!$contentType) {
            return true;
        }

        // check for substring as the content type could define attributes (e.g. charset)
        foreach ($this->contentTypes as $ct) {
            if (str_contains($contentType, $ct)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Inject editmode assets into response HTML
     *
     */
    protected function addEditmodeAssets(Document $document, Response $response): void
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
                $bodyHtml = "\n\n" . $this->editableConfigCollector->getHtml() . "\n\n";
                $bodyHtml .= "\n\n" . '<script src="' . $startupJavascript . '?_dc=' . Version::getRevision() . '"></script>' . "\n\n";

                $html = $this->insertBefore('</head>', $html, $headHtml);
                $html = $this->insertBefore('</body>', $html, $bodyHtml);

                $response->setContent($html);
            } else {
                $response->setContent('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
            }
        }
    }

    private function insertBefore(string $search, string $code, string $insert): string
    {
        $endPosition = strripos($code, $search);

        if (false !== $endPosition) {
            $code = substr_replace($code, $insert . "\n\n" . $search, $endPosition, 7);
        }

        return $code;
    }

    protected function buildHeadHtml(Document $document, string $language): string
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
        if (Pimcore::disableMinifyJs()) {
            foreach ($scripts as $script) {
                $headHtml .= '<script src="' . $script . '?_dc=' . Version::getRevision() . '"></script>';
                $headHtml .= "\n";
            }
        } else {
            $scriptContents = '';
            foreach ($scripts as $scriptUrl) {
                $scriptContents .= file_get_contents(PIMCORE_WEB_ROOT . $scriptUrl) . "\n\n\n";
            }

            $headHtml .= '<script src="' . $this->router->generate('pimcore_admin_misc_scriptproxy', \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents)) . '"></script>' . "\n";
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

    protected function getEditmodeLibraries(): array
    {
        $disableMinifyJs = Pimcore::disableMinifyJs();

        return [
            '/bundles/pimcoreadmin/js/pimcore/common.js',
            '/bundles/pimcoreadmin/js/lib/class.js',
            '/bundles/pimcoreadmin/extjs/js/ext-all' . ($disableMinifyJs ? '-debug' : '') . '.js',
        ];
    }

    protected function getEditmodeScripts(): array
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
                '/bundles/pimcoreadmin/js/pimcore/document/editables/area_abstract.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/areablock.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/area.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/pdf.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/embed.js',
                '/bundles/pimcoreadmin/js/pimcore/document/editables/manager.js',
                '/bundles/pimcoreadmin/js/pimcore/document/edit/helper.js',
            ],
            $this->bundleManager->getEditmodeJsPaths()
        );
    }

    protected function getEditmodeStylesheets(): array
    {
        return array_merge(
            [
                '/bundles/pimcoreadmin/css/icons.css',
                '/bundles/pimcoreadmin/extjs/css/PimcoreApp-all_1.css',
                '/bundles/pimcoreadmin/extjs/css/PimcoreApp-all_2.css',
                '/bundles/pimcoreadmin/css/editmode.css?_dc=' . time(),
            ],
            $this->bundleManager->getEditmodeCssPaths()
        );
    }
}
