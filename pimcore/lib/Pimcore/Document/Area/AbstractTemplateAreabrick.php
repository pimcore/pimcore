<?php

namespace Pimcore\Document\Area;

use Pimcore\Document\Area\Exception\ConfigurationException;
use Symfony\Component\Config\FileLocatorInterface;

abstract class AbstractTemplateAreabrick extends AbstractAreabrick
{
    /**
     * @var string
     */
    protected $bundleName;

    /**
     * @var FileLocatorInterface
     */
    protected $locator;

    /**
     * @var bool
     */
    protected $hasViewTemplate = true;

    /**
     * @var bool
     */
    protected $hasEditTemplate = false;

    /**
     * @param $bundleName
     * @param FileLocatorInterface $locator
     */
    public function __construct($bundleName, FileLocatorInterface $locator)
    {
        $this->bundleName = $bundleName;
        $this->locator    = $locator;
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
        $templatePath = $this->getTemplatePath($type);

        if (!is_file($templatePath)) {
            throw new ConfigurationException(sprintf(
                'Area %s is configured to have an %s template, but template was not found in %s',
                $this->getId(),
                $type,
                $templatePath
            ));
        }

        return $this->getTemplateReference($type);
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getTemplateReference($type)
    {
        return sprintf(
            '%s:Areas/%s:%s.%s',
            $this->bundleName,
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
            $this->bundleName,
            $this->getId(),
            $type,
            $this->getTemplateSuffix()
        );

        return $this->locator->locate($path);
    }
}
