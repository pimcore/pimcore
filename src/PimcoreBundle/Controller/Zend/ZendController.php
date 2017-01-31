<?php

namespace PimcoreBundle\Controller\Zend;

use PimcoreBundle\Controller\DocumentAwareInterface;
use PimcoreBundle\Controller\Traits\DocumentAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Zend\View\Model\ModelInterface;
use Zend\View\Model\ViewModel;

abstract class ZendController extends Controller implements ZendControllerInterface, DocumentAwareInterface
{
    use DocumentAwareTrait;

    /**
     * @var ModelInterface
     */
    protected $view;

    /**
     * @var ModelInterface|null
     */
    protected $layout;

    /**
     * @param FilterControllerEvent $event
     */
    public function preDispatch(FilterControllerEvent $event)
    {
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function postDispatch(FilterResponseEvent $event)
    {
    }

    /**
     * @param ModelInterface $view
     * @return $this
     */
    public function setView(ModelInterface $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ModelInterface
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @return null|ModelInterface
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $name
     * @param ModelInterface $child
     * @param string $childName
     *
     * @return $this
     */
    public function enableLayout($name, ModelInterface $child = null, $childName = 'content')
    {
        if (null === $child) {
            $child = $this->view;
        }

        $layout = $this->createLayout($child, $childName);
        $layout->setTemplate($name);

        $this->layout = $layout;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableLayout()
    {
        $this->layout = null;

        return $this;
    }

    /**
     * @param ModelInterface $child
     * @param string $childName
     *
     * @return ViewModel
     */
    protected function createLayout(ModelInterface $child, $childName = 'content')
    {
        $layout = new ViewModel();
        $layout->addChild($child, $childName);

        return $layout;
    }
}
