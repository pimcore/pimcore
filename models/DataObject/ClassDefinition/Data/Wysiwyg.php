<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\Element;
use Pimcore\Normalizer\NormalizerInterface;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Text;

class Wysiwyg extends Data implements ResourcePersistenceAwareInterface, QueryResourcePersistenceAwareInterface, TypeDeclarationSupportInterface, EqualComparisonInterface, VarExporterInterface, NormalizerInterface, IdRewriterInterface, PreGetDataInterface
{
    use DataObject\Traits\SimpleComparisonTrait;
    use Model\DataObject\ClassDefinition\Data\Extension\Text;
    use Extension\ColumnType;
    use Extension\QueryColumnType;
    use DataObject\Traits\SimpleNormalizerTrait;

    /**
     * Static type of this element
     *
     * @internal
     *
     * @var string
     */
    public $fieldtype = 'wysiwyg';

    /**
     * @internal
     *
     * @var string|int
     */
    public $width = 0;

    /**
     * @internal
     *
     * @var string|int
     */
    public $height = 0;

    /**
     * Type for the column to query
     *
     * @internal
     *
     * @var string
     */
    public $queryColumnType = 'longtext';

    /**
     * Type for the column
     *
     * @internal
     *
     * @var string
     */
    public $columnType = 'longtext';

    /**
     * @internal
     *
     * @var string
     */
    public string $toolbarConfig = '';

    /**
     * @internal
     *
     * @var bool
     */
    public $excludeFromSearchIndex = false;

    /**
     * @internal
     *
     * @var string|int
     */
    public $maxCharacters = 0;

    /**
     * @return string|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return string|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @param string|int $width
     *
     * @return $this
     */
    public function setWidth($width)
    {
        if (is_numeric($width)) {
            $width = (int)$width;
        }
        $this->width = $width;

        return $this;
    }

    /**
     * @param string|int $height
     *
     * @return $this
     */
    public function setHeight($height)
    {
        if (is_numeric($height)) {
            $height = (int)$height;
        }
        $this->height = $height;

        return $this;
    }

    /**
     * @param string $toolbarConfig
     */
    public function setToolbarConfig(string $toolbarConfig)
    {
        $this->toolbarConfig = $toolbarConfig;
    }

    /**
     * @return string
     */
    public function getToolbarConfig(): string
    {
        return $this->toolbarConfig;
    }

    /**
     * @return bool
     */
    public function isExcludeFromSearchIndex(): bool
    {
        return $this->excludeFromSearchIndex;
    }

    /**
     * @param bool $excludeFromSearchIndex
     *
     * @return $this
     */
    public function setExcludeFromSearchIndex(bool $excludeFromSearchIndex)
    {
        $this->excludeFromSearchIndex = $excludeFromSearchIndex;

        return $this;
    }

    /**
     * @return string|int
     */
    public function getMaxCharacters()
    {
        return $this->maxCharacters;
    }

    /**
     * @param string|int $maxCharacters
     */
    public function setMaxCharacters($maxCharacters)
    {
        $this->maxCharacters = $maxCharacters;
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataForResource
     *
     * @param string|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForResource($data, $object = null, $params = [])
    {
        return Text::wysiwygText($data);
    }

    /**
     * @see ResourcePersistenceAwareInterface::getDataFromResource
     *
     * @param string|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataFromResource($data, $object = null, $params = [])
    {
        return Text::wysiwygText($data);
    }

    /**
     * @see QueryResourcePersistenceAwareInterface::getDataForQueryResource
     *
     * @param string|null $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string|null
     */
    public function getDataForQueryResource($data, $object = null, $params = [])
    {
        $data = $this->getDataForResource($data, $object, $params);

        if (null !== $data) {
            $data = strip_tags($data, '<a><img>');
            $data = str_replace("\r\n", ' ', $data);
            $data = str_replace("\n", ' ', $data);
            $data = str_replace("\r", ' ', $data);
            $data = str_replace("\t", '', $data);
            $data = preg_replace('#[ ]+#', ' ', $data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getDataForSearchIndex($object, $params = [])
    {
        if ($this->isExcludeFromSearchIndex()) {
            return '';
        } else {
            return parent::getDataForSearchIndex($object, $params);
        }
    }

    /**
     * @see Data::getDataForEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataForEditmode($data, $object = null, $params = [])
    {
        return $this->getDataForResource($data, $object, $params);
    }

    /**
     * @see Data::getDataFromEditmode
     *
     * @param string $data
     * @param null|DataObject\Concrete $object
     * @param mixed $params
     *
     * @return string
     */
    public function getDataFromEditmode($data, $object = null, $params = [])
    {
        return $data;
    }

    /**
     * @param string|null $data
     *
     * @return array
     */
    public function resolveDependencies($data)
    {
        return Text::getDependenciesOfWysiwygText($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags($data, $tags = [])
    {
        return Text::getCacheTagsOfWysiwygText($data, $tags);
    }

    /**
     * {@inheritdoc}
     */
    public function checkValidity($data, $omitMandatoryCheck = false, $params = [])
    {
        if (!$omitMandatoryCheck && $this->getMandatory() && empty($data)) {
            throw new Element\ValidationException('Empty mandatory field [ '.$this->getName().' ]');
        }
        $dependencies = Text::getDependenciesOfWysiwygText($data);
        if (is_array($dependencies)) {
            foreach ($dependencies as $key => $value) {
                $el = Element\Service::getElementById($value['type'], $value['id']);
                if (!$el) {
                    throw new Element\ValidationException('Invalid dependency in wysiwyg text');
                }
            }
        }
    }

    /**
     * @param DataObject\Concrete|DataObject\Localizedfield|DataObject\Classificationstore|DataObject\Objectbrick\Data\AbstractData|DataObject\Fieldcollection\Data\AbstractData $container
     * @param array $params
     *
     * @return string
     */
    public function preGetData(/** mixed */ $container, /** array */ $params = []) // : mixed
    {
        $data = '';
        if ($container instanceof DataObject\Concrete) {
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Localizedfield || $container instanceof DataObject\Classificationstore) {
            $data = $params['data'];
        } elseif ($container instanceof DataObject\Fieldcollection\Data\AbstractData) {
            $data = $container->getObjectVar($this->getName());
        } elseif ($container instanceof DataObject\Objectbrick\Data\AbstractData) {
            $data = $container->getObjectVar($this->getName());
        }

        return Text::wysiwygText($data, [
                'object' => $container,
                'context' => $this,
            ]);
    }

    /** Generates a pretty version preview (similar to getVersionPreview) can be either html or
     * a image URL. See the https://github.com/pimcore/object-merger bundle documentation for details
     *
     * @param string|null $data
     * @param DataObject\Concrete|null $object
     * @param mixed $params
     *
     * @return array|string
     */
    public function getDiffVersionPreview($data, $object = null, $params = [])
    {
        if ($data) {
            $value = [];
            $value['html'] = $data;
            $value['type'] = 'html';

            return $value;
        } else {
            return '';
        }
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds(/** mixed */ $container, /** array */ $idMapping, /** array */ $params = []) /** :mixed */
    {
        $data = $this->getDataFromObjectParam($container, $params);

        if ($data) {
            $html = new DomCrawler($data);
            $es = $html->filter('a[pimcore_id], img[pimcore_id]');

            /** @var \DOMElement $el */
            foreach ($es as $el) {
                if ($el->hasAttribute('href') || $el->hasAttribute('src')) {
                    $type = $el->getAttribute('pimcore_type');
                    $id = (int) $el->getAttribute('pimcore_id');

                    if ($idMapping[$type][$id] ?? false) {
                        $el->setAttribute('pimcore_id', strtr($el->getAttribute('pimcore_id'), $idMapping[$type]));
                    }
                }
            }

            $data = $html->html();

            $html->clear();
            unset($html);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypeDeclaration(): ?string
    {
        return '?string';
    }

    /**
     * {@inheritdoc}
     */
    public function getReturnTypeDeclaration(): ?string
    {
        return '?string';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocInputType(): ?string
    {
        return 'string|null';
    }

    /**
     * {@inheritdoc}
     */
    public function getPhpdocReturnType(): ?string
    {
        return 'string|null';
    }
}
