<?php

namespace Pimcore\Document\Area;

use Pimcore\Bundle\PimcoreBundle\HttpKernel\BundleLocator\BundleLocatorInterface;
use Pimcore\Document\Area\Exception\ConfigurationException;
use Symfony\Component\Config\FileLocatorInterface;

abstract class AbstractTemplateAreabrick extends AbstractAreabrick
{
    /**
     * @var BundleLocatorInterface
     */
    protected $bundleLocator;

    /**
     * @var FileLocatorInterface
     */
    protected $fileLocator;

    /**
     * @var string
     */
    protected $bundleName;

    /**
     * @var array
     */
    protected $templateReferences = [];

    /**
     * @var bool
     */
    protected $hasViewTemplate = true;

    /**
     * @var bool
     */
    protected $hasEditTemplate = false;

    /**
     * @param BundleLocatorInterface $bundleLocator
     * @param FileLocatorInterface $locator
     */
    public function __construct(BundleLocatorInterface $bundleLocator, FileLocatorInterface $locator)
    {
        $this->bundleLocator = $bundleLocator;
        $this->fileLocator   = $locator;
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
     * @param string $type
     * @return string
     */
    protected function resolveTemplateReference($type)
    {
        if (!isset($this->templateReferences[$type])) {
            $templatePath = $this->getTemplatePath($type);

            if (null === $templatePath || !is_file($templatePath)) {
                throw new ConfigurationException(sprintf(
                    'Area %s is configured to have an %s template, but template was not found',
                    $this->getId(),
                    $type
                ));
            }

            $this->templateReferences[$type] = $this->getTemplateReference($type);
        }

        return $this->templateReferences[$type];
    }

    /**
     * @return string
     */
    protected function getBundleName()
    {
        if (null === $this->bundleName) {
            $this->bundleName = $this->bundleLocator->getBundle($this)->getName();
        }

        return $this->bundleName;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getTemplateReference($type)
    {
        return sprintf(
            '%s:Areas/%s:%s.%s',
            $this->getBundleName(),
            $this->getId(),
            $type,
            $this->getTemplateSuffix()
        );
    }

    /**
     * @param $type
     * @return string
     */
    protected function getTemplatePath($type)
    {
        $path = sprintf(
            '@%s/Resources/views/Areas/%s/%s.%s',
            $this->getBundleName(),
            $this->getId(),
            $type,
            $this->getTemplateSuffix()
        );

        try {
            return $this->fileLocator->locate($path);
        } catch (\Exception $e) {
            // noop
        }
    }
}
