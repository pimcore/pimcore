<?php

namespace Pimcore\Document\Area;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModel;
use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;
use Pimcore\Translate;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class AreaHandlerStrategy implements AreaHandlerInterface
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
     * @param AreabrickManagerInterface $brickManager
     * @param EngineInterface $templating
     */
    public function __construct(AreabrickManagerInterface $brickManager, EngineInterface $templating)
    {
        $this->brickManager = $brickManager;
        $this->templating   = $templating;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Tag $tag)
    {
        return $tag->getView() instanceof ViewModelInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableAreas(Tag\Areablock $tag, array $options)
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
            $icon = ''; // TODO icons

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
    public function renderFrontend(Info $info, array $params)
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
}
