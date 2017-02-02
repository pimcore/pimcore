<?php

namespace Pimcore\Bundle\PimcoreZendBundle\Templating;

use Symfony\Component\Templating\TemplateReference as BaseTemplateReference;

class ZendTemplateReference extends BaseTemplateReference
{
    /**
     * @param string $bundle
     * @param string $controller
     * @param string $name
     */
    public function __construct($bundle = null, $controller = null, $name = null)
    {
        $this->parameters = array(
            'bundle'     => $bundle,
            'controller' => $controller,
            'name'       => $name,
            'engine'     => 'zend'
        );
    }

    /**
     * Returns the path to the template
     *  - as a path when the template is not part of a bundle
     *  - as a resource when the template is part of a bundle.
     *
     * @return string A path to the template or a resource
     */
    public function getPath()
    {
        $controller = str_replace('\\', '/', $this->get('controller'));

        $path = (empty($controller) ? '' : $controller . '/') . $this->get('name') . '.phtml';

        return empty($this->parameters['bundle']) ? 'views/' . $path : '@' . $this->get('bundle') . '/Resources/views/' . $path;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogicalName()
    {
        return sprintf('%s:%s:%s.%s', $this->parameters['bundle'], $this->parameters['controller'], $this->parameters['name'], 'phtml');
    }
}
