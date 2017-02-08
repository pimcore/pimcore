<?php

namespace Pimcore\Document\Area;

use Pimcore\Bundle\PimcoreBundle\Templating\Model\ViewModelInterface;
use Pimcore\Model\Document\Tag\Area\Info;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

class AreaRenderingStrategy implements AreaRenderingStrategyInterface
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
    public function supports(Info $info)
    {
        return $info->getTag()->getView() instanceof ViewModelInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function renderFrontend(Info $info, array $params)
    {
        $tag   = $info->getTag();
        $view  = $tag->getView();
        $brick = $this->brickManager->get($info->getId());

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
