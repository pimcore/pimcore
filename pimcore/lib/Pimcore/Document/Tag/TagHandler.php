<?php

namespace Pimcore\Document\Tag;

use Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator\BundleLocatorInterface;
use Pimcore\Bundle\PimcoreBundle\Service\WebPathResolver;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Document\Area\AreabrickManagerInterface;
use Pimcore\Model\Document\PageSnippet;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Bundle\PimcoreBundle\Templating\Renderer\ActionRenderer;
use Pimcore\Translate;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class TagHandler implements TagHandlerInterface
{
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
     * @var ActionRenderer
     */
    protected $actionRenderer;

    /**
     * @param AreabrickManagerInterface $brickManager
     * @param EngineInterface $templating
     * @param BundleLocatorInterface $bundleLocator
     * @param WebPathResolver $webPathResolver
     * @param ActionRenderer $actionRenderer
     */
    public function __construct(
        AreabrickManagerInterface $brickManager,
        EngineInterface $templating,
        BundleLocatorInterface $bundleLocator,
        WebPathResolver $webPathResolver,
        ActionRenderer $actionRenderer
    )
    {
        $this->brickManager    = $brickManager;
        $this->templating      = $templating;
        $this->bundleLocator   = $bundleLocator;
        $this->webPathResolver = $webPathResolver;
        $this->actionRenderer  = $actionRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($view)
    {
        return $view instanceof ViewModelInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAreablockAreas(Tag\Areablock $tag, array $options)
    {
        /** @var ViewModel $view */
        $view = $tag->getView();

        $areas = [];
        foreach ($this->brickManager->getBricks() as $brick) {
            // don't show disabled bricks
            if (!isset($options['dontCheckEnabled']) || !$options['dontCheckEnabled']) {
                if (!$tag->isBrickEnabled($brick->getId())) {
                    continue;
                }
            }

            if (!(empty($options['allowed']) || in_array($brick->getId(), $options['allowed']))) {
                continue;
            }

            $name = $brick->getName();
            $desc = $brick->getDescription();
            $icon = $brick->getIcon();

            // autoresolve icon as <bundleName>/Resources/public/areas/<id>/icon.png
            if (null === $icon) {
                $bundle = $this->bundleLocator->getBundle($brick);

                // check if file exists
                $iconPath = sprintf('%s/Resources/public/areas/%s/icon.png', $bundle->getPath(), $brick->getId());
                if (file_exists($iconPath)) {
                    // build URL to icon
                    $icon = $this->webPathResolver->getPath($bundle, 'areas/' . $brick->getId(), 'icon.png');
                }
            }

            if ($view->editmode) {
                $name = Translate::transAdmin($name);
                $desc = Translate::transAdmin($desc);
            }

            $areas[$brick->getId()] = [
                'name'        => $name,
                'description' => $desc,
                'type'        => $brick->getId(),
                'icon'        => $icon,
            ];
        }

        return $areas;
    }

    /**
     * {@inheritdoc}
     */
    public function renderAreaFrontend(Info $info, array $params)
    {
        $tag   = $info->getTag();
        $view  = $tag->getView();
        $brick = $this->brickManager->getBrick($info->getId());

        // assign parameters to view
        $view->getParameters()->add($params);

        // call action
        $brick->action($info);

        if (null === $brick->getViewTemplate()) {
            return;
        }

        $editmode = $view->editmode;

        echo $brick->getHtmlTagOpen($info);

        if (null !== $brick->getEditTemplate() && $editmode) {
            echo '<div class="pimcore_area_edit_button_' . $tag->getName() . ' pimcore_area_edit_button"></div>';

            // forces the editmode in view independent if there's an edit or not
            if (!array_key_exists('forceEditInView', $params) || !$params['forceEditInView']) {
                $view->editmode = false;
            }
        }

        // render view template
        echo $this->templating->render(
            $brick->getViewTemplate(),
            $view->getParameters()->all()
        );

        if (null !== $brick->getEditTemplate() && $editmode) {
            $view->editmode = true;

            echo '<div class="pimcore_area_editmode_' . $tag->getName() . ' pimcore_area_editmode pimcore_area_editmode_hidden">';

            // render edit template
            echo $this->templating->render(
                $brick->getEditTemplate(),
                $view->getParameters()->all()
            );

            echo '</div>';
        }

        echo $brick->getHtmlTagClose($info);

        // call post render
        $brick->postRenderAction($info);
    }

    /**
     * {@inheritdoc}
     */
    public function renderAction($view, $controller, $action, $parent = null, array $params = [])
    {
        $document = $params['document'];
        if ($document && $document instanceof PageSnippet) {
            $params = $this->actionRenderer->addDocumentParams($document, $params);
        }

        $controller = $this->actionRenderer->createControllerReference(
            $parent,
            $controller,
            $action,
            $params
        );

        return $this->actionRenderer->render($controller);
    }
}
