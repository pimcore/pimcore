<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Document\Editable;

use Pimcore\Extension\Document\Areabrick\AreabrickInterface;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Extension\Document\Areabrick\Exception\ConfigurationException;
use Pimcore\Extension\Document\Areabrick\TemplateAreabrickInterface;
use Pimcore\Http\Request\Resolver\EditmodeResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Http\ResponseStack;
use Pimcore\HttpKernel\BundleLocator\BundleLocatorInterface;
use Pimcore\HttpKernel\WebPathResolver;
use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Model\Document\PageSnippet;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\FragmentRendererInterface;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class EditableHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AreabrickManagerInterface
     */
    protected $brickManager;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var BundleLocatorInterface
     */
    protected $bundleLocator;

    /**
     * @var WebPathResolver
     */
    protected $webPathResolver;

    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ResponseStack
     */
    protected $responseStack;

    /**
     * @var array
     */
    protected $brickTemplateCache = [];

    /**
     * @var EditmodeResolver
     */
    protected $editmodeResolver;

    /**
     * @var HttpKernelRuntime
     */
    protected $httpKernelRuntime;

    /**
     * @var FragmentRendererInterface
     */
    protected $fragmentRenderer;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    public const ATTRIBUTE_AREABRICK_INFO = '_pimcore_areabrick_info';

    private EditmodeEditableDefinitionCollector $definitionCollector;

    /**
     * @param AreabrickManagerInterface $brickManager
     * @param EngineInterface $templating
     * @param BundleLocatorInterface $bundleLocator
     * @param WebPathResolver $webPathResolver
     * @param RequestHelper $requestHelper
     * @param TranslatorInterface $translator
     * @param ResponseStack $responseStack
     * @param EditmodeResolver $editmodeResolver
     * @param HttpKernelRuntime $httpKernelRuntime
     * @param EditmodeEditableDefinitionCollector $definitionCollector
     */
    public function __construct(
        AreabrickManagerInterface $brickManager,
        EngineInterface $templating,
        BundleLocatorInterface $bundleLocator,
        WebPathResolver $webPathResolver,
        RequestHelper $requestHelper,
        TranslatorInterface $translator,
        ResponseStack $responseStack,
        EditmodeResolver $editmodeResolver,
        HttpKernelRuntime $httpKernelRuntime,
        EditmodeEditableDefinitionCollector $definitionCollector,
        FragmentRendererInterface $fragmentRenderer,
        RequestStack $requestStack
    ) {
        $this->brickManager = $brickManager;
        $this->templating = $templating;
        $this->bundleLocator = $bundleLocator;
        $this->webPathResolver = $webPathResolver;
        $this->requestHelper = $requestHelper;
        $this->translator = $translator;
        $this->responseStack = $responseStack;
        $this->editmodeResolver = $editmodeResolver;
        $this->httpKernelRuntime = $httpKernelRuntime;
        $this->definitionCollector = $definitionCollector;
        $this->fragmentRenderer = $fragmentRenderer;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function isBrickEnabled(Editable $editable, $brick)
    {
        if ($brick instanceof AreabrickInterface) {
            $brick = $brick->getId();
        }

        return $this->brickManager->isEnabled($brick);
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAreablockAreas(Editable\Areablock $editable, array $options)
    {
        $areas = [];
        foreach ($this->brickManager->getBricks() as $brick) {
            // don't show disabled bricks
            if (!isset($options['dontCheckEnabled']) || !$options['dontCheckEnabled']) {
                if (!$this->isBrickEnabled($editable, $brick)) {
                    continue;
                }
            }

            if (!(empty($options['allowed']) || in_array($brick->getId(), $options['allowed']))) {
                continue;
            }

            $name = $brick->getName();
            $desc = $brick->getDescription();
            $icon = $brick->getIcon();
            $limit = $options['limits'][$brick->getId()] ?? null;

            $hasDialogBoxConfiguration = $brick instanceof EditableDialogBoxInterface;

            // autoresolve icon as <bundleName>/Resources/public/areas/<id>/icon.png
            if (null === $icon) {
                $bundle = null;
                try {
                    $bundle = $this->bundleLocator->getBundle($brick);

                    // check if file exists
                    $iconPath = sprintf('%s/Resources/public/areas/%s/icon.png', $bundle->getPath(), $brick->getId());
                    if (file_exists($iconPath)) {
                        // build URL to icon
                        $icon = $this->webPathResolver->getPath($bundle, 'areas/' . $brick->getId(), 'icon.png');
                    }
                } catch (\Exception $e) {
                    $icon = '';
                }
            }

            if ($this->editmodeResolver->isEditmode()) {
                $name = $this->translator->trans($name);
                $desc = $this->translator->trans($desc);
            }

            $areas[$brick->getId()] = [
                'name' => $name,
                'description' => $desc,
                'type' => $brick->getId(),
                'icon' => $icon,
                'limit' => $limit,
                'needsReload' => $brick->needsReload(),
                'hasDialogBoxConfiguration' => $hasDialogBoxConfiguration,
            ];
        }

        return $areas;
    }

    /**
     * @param Info $info
     * @param array $templateParams
     *
     * @return string
     */
    public function renderAreaFrontend(Info $info, $templateParams = []): string
    {
        $brick = $this->brickManager->getBrick($info->getId());

        $request = $this->requestHelper->getCurrentRequest();
        $brickInfoRestoreValue = $request->attributes->get(self::ATTRIBUTE_AREABRICK_INFO);
        $request->attributes->set(self::ATTRIBUTE_AREABRICK_INFO, $info);

        $info->setRequest($request);

        // call action
        $this->handleBrickActionResult($brick->action($info));

        $params = $info->getParams();
        $params['brick'] = $info;
        $params['info'] = $info;
        $params['instance'] = $brick;

        // check if view template exists and throw error before open tag is rendered
        $viewTemplate = $this->resolveBrickTemplate($brick, 'view');
        if (!$this->templating->exists($viewTemplate)) {
            $e = new ConfigurationException(sprintf(
                'The view template "%s" for areabrick %s does not exist',
                $viewTemplate,
                $brick->getId()
            ));

            $this->logger->error($e->getMessage());

            throw $e;
        }

        // general parameters
        $editmode = $this->editmodeResolver->isEditmode();

        if (!isset($templateParams['isAreaBlock'])) {
            $templateParams['isAreaBlock'] = false;
        }

        // render complete areabrick
        // passing the engine interface is necessary otherwise rendering a
        // php template inside the twig template returns the content of the php file
        // instead of actually parsing the php template
        $html = $this->templating->render('@PimcoreCore/Areabrick/wrapper.html.twig', array_merge([
            'brick' => $brick,
            'info' => $info,
            'templating' => $this->templating,
            'editmode' => $editmode,
            'viewTemplate' => $viewTemplate,
            'viewParameters' => $params,
        ], $templateParams));

        if ($brickInfoRestoreValue === null) {
            $request->attributes->remove(self::ATTRIBUTE_AREABRICK_INFO);
        } else {
            $request->attributes->set(self::ATTRIBUTE_AREABRICK_INFO, $brickInfoRestoreValue);
        }

        // call post render
        $this->handleBrickActionResult($brick->postRenderAction($info));

        return $html;
    }

    protected function handleBrickActionResult($result)
    {

        // if the action result is a response object, push it onto the
        // response stack. this response will be used by the ResponseStackListener
        // and sent back to the client
        if ($result instanceof Response) {
            $this->responseStack->push($result);
        }
    }

    /**
     * Try to get the brick template from get*Template method. If method returns null and brick implements
     * TemplateAreabrickInterface fall back to auto-resolving the template reference. See interface for examples.
     *
     * @param AreabrickInterface $brick
     * @param string $type
     *
     * @return mixed|null|string
     */
    protected function resolveBrickTemplate(AreabrickInterface $brick, $type)
    {
        $cacheKey = sprintf('%s.%s', $brick->getId(), $type);
        if (isset($this->brickTemplateCache[$cacheKey])) {
            return $this->brickTemplateCache[$cacheKey];
        }

        $template = null;
        if ($type === 'view') {
            $template = $brick->getTemplate();
        }

        if (null === $template) {
            if ($brick instanceof TemplateAreabrickInterface) {
                $template = $this->buildBrickTemplateReference($brick, $type);
            } else {
                $e = new ConfigurationException(sprintf(
                    'Brick %s is configured to have a %s template but does not return a template path and does not implement %s',
                    $brick->getId(),
                    $type,
                    TemplateAreabrickInterface::class
                ));

                $this->logger->error($e->getMessage());

                throw $e;
            }
        }

        $this->brickTemplateCache[$cacheKey] = $template;

        return $template;
    }

    /**
     * Return either bundle or global (= app/Resources) template reference
     *
     * @param TemplateAreabrickInterface $brick
     * @param string $type
     *
     * @return string
     */
    protected function buildBrickTemplateReference(TemplateAreabrickInterface $brick, $type)
    {
        if ($brick->getTemplateLocation() === TemplateAreabrickInterface::TEMPLATE_LOCATION_BUNDLE) {
            $bundle = $this->bundleLocator->getBundle($brick);
            $bundleName = $bundle->getName();
            if (str_ends_with($bundleName, 'Bundle')) {
                $bundleName = substr($bundleName, 0, -6);
            }

            $templateReference = '';

            foreach (['areas', 'Areas'] as $folderName) {
                $templateReference = sprintf(
                    '@%s/%s/%s/%s.%s',
                    $bundleName,
                    $folderName,
                    $brick->getId(),
                    $type,
                    $brick->getTemplateSuffix()
                );

                if ($this->templating->exists($templateReference)) {
                    return $templateReference;
                }
            }

            // return the last reference, even we know that it doesn't exist -> let care the templating engine
            return $templateReference;
        } else {
            return sprintf(
                'areas/%s/%s.%s',
                $brick->getId(),
                $type,
                $brick->getTemplateSuffix()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderAction($controller, array $attributes = [], array $query = [])
    {
        $document = $attributes['document'] ?? null;
        if ($document && $document instanceof PageSnippet) {
            unset($attributes['document']);
            $attributes = $this->addDocumentAttributes($document, $attributes);
        }

        $uri = new ControllerReference($controller, $attributes, $query);

        if($this->requestHelper->hasCurrentRequest()) {
            return $this->httpKernelRuntime->renderFragment($uri, $attributes);
        } else {
            // this case could happen when rendering on CLI, e.g. search-reindex ...
            $request = $this->requestHelper->createRequestWithContext();
            $this->requestStack->push($request);
            $response = $this->fragmentRenderer->render($uri, $request, $attributes);
            $this->requestStack->pop();

            return $response;
        }
    }

    /**
     * @param PageSnippet $document
     * @param array $attributes
     *
     * @return array
     */
    public function addDocumentAttributes(PageSnippet $document, array $attributes = [])
    {
        // The CMF dynamic router sets the 2 attributes contentDocument and contentTemplate to set
        // a route's document and template. Those attributes are later used by controller listeners to
        // determine what to render. By injecting those attributes into the sub-request we can rely on
        // the same rendering logic as in the routed request.
        $attributes[DynamicRouter::CONTENT_KEY] = $document;

        if ($document->getTemplate()) {
            $attributes[DynamicRouter::CONTENT_TEMPLATE] = $document->getTemplate();
        }

        if ($language = $document->getProperty('language')) {
            $attributes['_locale'] = $language;
        }

        return $attributes;
    }
}
