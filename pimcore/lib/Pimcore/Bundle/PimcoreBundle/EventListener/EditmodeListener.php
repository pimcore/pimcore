<?php

namespace Pimcore\Bundle\PimcoreBundle\EventListener;

use Pimcore\Bundle\PimcoreBundle\Service\Request\DocumentResolver;
use Pimcore\Bundle\PimcoreBundle\Service\Request\EditmodeResolver;
use Pimcore\Config;
use Pimcore\ExtensionManager;
use Pimcore\Model\Document;
use Pimcore\Tool\Admin;
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

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var DocumentFallbackListener
     */
    protected $documentResolver;

    /**
     * @var array
     */
    protected $contentTypes = [
        'text/html'
    ];

    /**
     * @param EditmodeResolver $editmodeResolver
     * @param DocumentFallbackListener $documentResolver
     */
    public function __construct(EditmodeResolver $editmodeResolver, DocumentResolver $documentResolver)
    {
        $this->editmodeResolver = $editmodeResolver;
        $this->documentResolver = $documentResolver;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST  => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }


    public function onKernelRequest(GetResponseEvent $event)
    {
        // TODO editmode is available to logged in users only
        $editmode = $this->editmodeResolver->isEditmode($event->getRequest());

        // TODO this can be removed later
        \Zend_Registry::set('pimcore_editmode', $editmode);
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->editmodeResolver->isEditmode($event->getRequest())) {
            return;
        }

        if (!$this->contentTypeMatches($response)) {
            return;
        }

        $document = $this->documentResolver->getDocument($request);
        if (!$document) {
            return;
        }

        $this->addEditmodeAssets($document, $response);
    }

    /**
     * @param Response $response
     * @return bool
     */
    protected function contentTypeMatches(Response $response)
    {
        $contentType = $response->headers->get('Content-Type');
        if (!$contentType || in_array($contentType, $this->contentTypes)) {
            return true;
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

            $htmlElement = preg_match("/<html[^a-zA-Z]?( [^>]+)?>/", $html);
            $headElement = preg_match("/<head[^a-zA-Z]?( [^>]+)?>/", $html);
            $bodyElement = preg_match("/<body[^a-zA-Z]?( [^>]+)?>/", $html);

            $skipCheck = false;

            // if there's no head and no body, create a wrapper including these elements
            // add html headers for snippets in editmode, so there is no problem with javascript
            if (!$headElement && !$bodyElement && !$htmlElement) {
                $html      = "<!DOCTYPE html>\n<html>\n<head></head><body>" . $html . "</body></html>";
                $skipCheck = true;
            }

            if ($skipCheck || ($headElement && $bodyElement && $htmlElement)) {
                $startupJavascript = "/pimcore/static6/js/pimcore/document/edit/startup.js";

                $headHtml = $this->buildHeadHtml($document);
                $bodyHtml = "\n\n" . '<script type="text/javascript" src="' . $startupJavascript . '?_dc=' . Version::$revision . '"></script>' . "\n\n";

                $html = preg_replace("@</head>@i", $headHtml . "\n\n</head>", $html, 1);
                $html = preg_replace("@</body>@i", $bodyHtml . "\n\n</body>", $html, 1);

                $response->setContent($html);
            } else {
                $response->setContent('<div style="font-size:30px; font-family: Arial; font-weight:bold; color:red; text-align: center; margin: 40px 0">You have to define a &lt;html&gt;, &lt;head&gt;, &lt;body&gt;<br />HTML-tag in your view/layout markup!</div>');
            }
        }
    }

    /**
     * @param Document $document
     * @return string
     */
    protected function buildHeadHtml(Document $document)
    {
        $config      = Config::getSystemConfig();
        $libraries   = $this->getEditmodeLibraries();
        $scripts     = $this->getEditmodeScripts();
        $stylesheets = $this->getEditmodeStylesheets();

        $pluginAssets = $this->getPluginAssets();
        if (!empty($pluginAssets['js'])) {
            $scripts = array_merge($scripts, $pluginAssets['js']);
        }

        if (!empty($pluginAssets['css'])) {
            $stylesheets = array_merge($stylesheets, $pluginAssets['css']);
        }

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
                $scriptContents .= file_get_contents(PIMCORE_DOCUMENT_ROOT . $scriptUrl) . "\n\n\n";
            }

            $headHtml .= '<script type="text/javascript" src="' . \Pimcore\Tool\Admin::getMinimizedScriptPath($scriptContents) . '"></script>' . "\n";
        }

        $user = \Pimcore\Tool\Authentication::authenticateSession();
        $lang = $user->getLanguage();

        $headHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-system/language/' . $lang . '/?_dc=' . Version::$revision . '"></script>' . "\n";
        $headHtml .= '<script type="text/javascript" src="/admin/misc/json-translations-admin/language/' . $lang . '/?_dc=' . Version::$revision . '"></script>' . "\n";
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
     * Add plugin editmode JS and CSS
     *
     * @return array
     */
    protected function getPluginAssets()
    {
        $assets = [
            'js'  => [],
            'css' => []
        ];

        $pluginConfigs = ExtensionManager::getPluginConfigs();
        if (empty($pluginConfigs)) {
            return $assets;
        }

        $pluginVersions = ['-extjs6'];

        foreach ($pluginConfigs as $pluginConfig) {
            try {
                $assets = $this->processPluginConfig($pluginConfig, $pluginVersions, $assets);
            } catch (\Exception $e) {
                $this->logger->alert('There is a problem with the plugin configuration');
                $this->logger->alert($e);
            }
        }
    }

    /**
     * Load plugin editmode files
     *
     * @param array $pluginConfig
     * @param array $pluginVersions
     * @param array $assets
     * @return array
     */
    protected function processPluginConfig(array $pluginConfig, array $pluginVersions, array $assets)
    {
        foreach (array_keys($assets) as $assetType) {
            $assets[$assetType] = array_merge(
                $assets[$assetType],
                $this->getPluginAssetTypeFiles($assetType, $pluginConfig, $pluginVersions)
            );
        }

        return $assets;
    }

    /**
     * Load plugin editmode files for an asset type (e.g. JS)
     *
     * @param $type
     * @param array $pluginConfig
     * @param array $pluginVersions
     * @return array
     */
    protected function getPluginAssetTypeFiles($type, array $pluginConfig, array $pluginVersions)
    {
        $baseConfigKey = sprintf('pluginDocumentEditmode%sPaths', ucfirst($type));

        $files = [];
        foreach ($pluginVersions as $pluginVersion) {
            $configKey = $baseConfigKey . $pluginVersion;

            if (array_key_exists($configKey, $pluginConfig['plugin'])
                && is_array($pluginConfig['plugin'][$configKey])
                && isset($pluginConfig['plugin'][$configKey]['path'])
            ) {
                $path = $pluginConfig['plugin'][$configKey]['path'];

                if (is_array($path)) {
                    $files = $path;
                    break;
                } elseif (null !== $path) {
                    $files[] = $path;
                    break;
                }
            }
        }

        // manipulate path for frontend
        $result = [];
        if (is_array($files) and count($files) > 0) {
            foreach ($files as $file) {
                if (is_file(PIMCORE_PLUGINS_PATH . $file)) {
                    $result[] = '/plugins' . $file;
                }
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getEditmodeLibraries()
    {
        return [
            '/pimcore/static6/js/pimcore/namespace.js',
            '/pimcore/static6/js/lib/prototype-light.js',
            '/pimcore/static6/js/lib/jquery.min.js',
            '/pimcore/static6/js/lib/ext/ext-all.js',
            '/pimcore/static6/js/lib/ckeditor/ckeditor.js'
        ];
    }

    /**
     * @return array
     */
    protected function getEditmodeScripts()
    {
        return [
            '/pimcore/static6/js/pimcore/functions.js',
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
        ];
    }

    /**
     * @return array
     */
    protected function getEditmodeStylesheets()
    {
        return [
            '/pimcore/static6/css/icons.css',
            '/pimcore/static6/css/editmode.css?_dc=' . time()
        ];
    }
}
