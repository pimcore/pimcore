<?php

namespace Pimcore\Document\Area;

use Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator\BundleLocatorInterface;

abstract class AbstractTemplateAreabrick extends AbstractAreabrick
{
    const TEMPLATE_LOCATION_GLOBAL = 'global';
    const TEMPLATE_LOCATION_BUNDLE = 'bundle';

    /**
     * @var bool
     */
    protected $hasViewTemplate = true;

    /**
     * @var bool
     */
    protected $hasEditTemplate = false;

    /**
     * @var BundleLocatorInterface
     */
    protected $bundleLocator;

    /**
     * @var array
     */
    protected $templateReferences = [];

    /**
     * @param BundleLocatorInterface $bundleLocator
     */
    public function __construct(BundleLocatorInterface $bundleLocator)
    {
        $this->bundleLocator = $bundleLocator;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewTemplate()
    {
        if (!$this->hasViewTemplate) {
            return null;
        }

        return $this->resolveTemplateReference('view');
    }

    /**
     * {@inheritdoc}
     */
    public function getEditTemplate()
    {
        if (!$this->hasEditTemplate) {
            return null;
        }

        return $this->resolveTemplateReference('edit');
    }

    /**
     * Returns view suffix used to auto-build view names
     *
     * @return string
     */
    protected function getTemplateSuffix()
    {
        return 'phtml';
    }

    /**
     * Determines if template should be auto-located in area bundle or in app/Resources
     *
     * @return string
     */
    protected function getTemplateLocation()
    {
        return static::TEMPLATE_LOCATION_BUNDLE;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function resolveTemplateReference($type)
    {
        if (!isset($this->templateReferences[$type])) {
            $this->templateReferences[$type] = $this->getTemplateReference($type);
        }

        return $this->templateReferences[$type];
    }

    /**
     * @return string
     */
    protected function getBundleName()
    {
        return $this->bundleLocator->getBundle($this)->getName();
    }

    /**
     * Return either bundle or global (= app/Resources) template reference
     *
     * @param string $type
     * @return string
     */
    protected function getTemplateReference($type)
    {
        if ($this->getTemplateLocation() === static::TEMPLATE_LOCATION_BUNDLE) {
            return sprintf(
                '%s:Areas/%s:%s.%s',
                $this->getBundleName(),
                $this->getId(),
                $type,
                $this->getTemplateSuffix()
            );
        } else {
            return sprintf(
                'Areas/%s/%s.%s',
                $this->getId(),
                $type,
                $this->getTemplateSuffix()
            );
        }
    }
}
