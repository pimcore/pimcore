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
use Pimcore\Templating\Renderer\ActionRenderer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EditableHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var AreabrickManagerInterface
     */
    protected $brickManager;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var BundleLocatorInterface
     */
    protected $bundleLocator;

    /**
     * @var WebPathResolver
     */
    protected $webPathResolver;

    /**
     * @var ActionRenderer
     */
    protected $actionRenderer;

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

    public const ATTRIBUTE_AREABRICK_INFO = '_pimcore_areabrick_info';

    /**
     * @param AreabrickManagerInterface $brickManager
     * @param Environment $twig
     * @param BundleLocatorInterface $bundleLocator
     * @param WebPathResolver $webPathResolver
     * @param ActionRenderer $actionRenderer
     * @param RequestHelper $requestHelper
     * @param TranslatorInterface $translator
     * @param ResponseStack $responseStack
     * @param EditmodeResolver $editmodeResolver
     */
    public function __construct(
        AreabrickManagerInterface $brickManager,
        Environment $twig,
        BundleLocatorInterface $bundleLocator,
        WebPathResolver $webPathResolver,
        ActionRenderer $actionRenderer,
        RequestHelper $requestHelper,
        TranslatorInterface $translator,
        ResponseStack $responseStack,
        EditmodeResolver $editmodeResolver
    ) {
        $this->brickManager = $brickManager;
        $this->twig = $twig;
        $this->bundleLocator = $bundleLocator;
        $this->webPathResolver = $webPathResolver;
        $this->actionRenderer = $actionRenderer;
        $this->requestHelper = $requestHelper;
        $this->translator = $translator;
        $this->responseStack = $responseStack;
        $this->editmodeResolver = $editmodeResolver;
    }

    /**
     * @inheritDoc
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
                'hasDialogBoxConfiguration' => $hasDialogBoxConfiguration,
            ];
        }

        return $areas;
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info)
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
        $loader = $this->twig->getLoader();

        if (!$loader->exists($viewTemplate)) {
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

        // render complete areabrick
        // passing the engine interface is necessary otherwise rendering a
        // php template inside the twig template returns the content of the php file
        // instead of actually parsing the php template
        echo $this->twig->render('@PimcoreCore/Areabrick/wrapper.html.twig', [
            'brick' => $brick,
            'info' => $info,
            'twig' => $this->twig,
            'editmode' => $editmode,
            'viewTemplate' => $viewTemplate,
            'viewParameters' => $params,
        ]);

        if ($brickInfoRestoreValue === null) {
            $request->attributes->remove(self::ATTRIBUTE_AREABRICK_INFO);
        } else {
            $request->attributes->set(self::ATTRIBUTE_AREABRICK_INFO, $brickInfoRestoreValue);
        }

        // call post render
        $this->handleBrickActionResult($brick->postRenderAction($info));
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

            return sprintf(
                '%s:Areas/%s:%s.%s',
                $bundle->getName(),
                $brick->getId(),
                $type,
                $brick->getTemplateSuffix()
            );
        } else {
            return sprintf(
                'Areas/%s/%s.%s',
                $brick->getId(),
                $type,
                $brick->getTemplateSuffix()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderAction($controller, $action, $parent = null, array $attributes = [], array $query = [], array $options = [])
    {
        $document = $attributes['document'] ?? null;
        if ($document && $document instanceof PageSnippet) {
            unset($attributes['document']);
            $attributes = $this->actionRenderer->addDocumentAttributes($document, $attributes);
        }

        $uri = $this->actionRenderer->createControllerReference(
            $parent,
            $controller,
            $action,
            $attributes,
            $query
        );

        return $this->actionRenderer->render($uri, $options);
    }
}
