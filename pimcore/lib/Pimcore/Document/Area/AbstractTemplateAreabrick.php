<?php

namespace Pimcore\Document\Area;

use Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator\BundleLocatorInterface;

/**
 * Auto-resolves view and edit templates if has*Template properties are set. Depending on the result of getTemplateLocation
 * and getTemplateSuffix it builds the following template references:
 *
 * - <currentBundle>:Areas/<brickId>/(view|edit).<suffix>
 * - Areas/<brickId>/(view|edit).<suffix> -> resolves to app/Resources
 */
abstract class AbstractTemplateAreabrick extends AbstractAreabrick
{
    const TEMPLATE_LOCATION_GLOBAL = 'global';
    const TEMPLATE_LOCATION_BUNDLE = 'bundle';

    const TEMPLATE_SUFFIX_ZEND_VIEW = 'phtml';
    const TEMPLATE_SUFFIX_TWIG      = 'html.twig';

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
     * Determines if template should be auto-located in area bundle or in app/Resources
     *
     * @return string
     */
    protected function getTemplateLocation()
    {
        return static::TEMPLATE_LOCATION_BUNDLE;
    }

    /**
     * Returns view suffix used to auto-build view names
     *
     * @return string
     */
    protected function getTemplateSuffix()
    {
        return static::TEMPLATE_SUFFIX_ZEND_VIEW;
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
