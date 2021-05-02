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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Bundle\CoreBundle\EventListener\Frontend\FullPageCacheListener;
use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Http\Request\Resolver\OutputTimestampResolver;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Scheduledblock extends Block implements BlockInterface
{
    /**
     * @internal
     *
     * @var array|null
     */
    protected $cachedCurrentElement = null;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'scheduledblock';
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->indices = $data;

        usort($this->indices, function ($left, $right) {
            if ($left['date'] == $right['date']) {
                return 0;
            }

            return ($left['date'] < $right['date']) ? -1 : 1;
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function setDefault()
    {
        if (empty($this->indices)) {
            $this->indices[] = [
                'key' => 0,
                'date' => time(),
            ];
        }

        return $this;
    }

    private function filterElements()
    {
        if ($this->getEditmode()) {
            return $this->indices;
        } else {
            if ($this->cachedCurrentElement) {
                return [$this->cachedCurrentElement];
            }

            $outputTimestampResolver = \Pimcore::getContainer()->get(OutputTimestampResolver::class);
            $outputTimestamp = $outputTimestampResolver->getOutputTimestamp();

            $currentElement = null;
            $nextElement = null; //needed for calculating cache lifetime
            foreach ($this->indices as $element) {
                if ($element['date'] <= $outputTimestamp) {
                    $currentElement = $element;
                } elseif (empty($nextElement)) {
                    //set first element after output timestamp as next element
                    $nextElement = $element;
                } else {
                    break;
                }
            }

            $this->updateOutputCacheLifetime($outputTimestamp, $nextElement);

            if ($currentElement) {
                $this->cachedCurrentElement = $currentElement;

                return [$currentElement];
            } else {
                return null;
            }
        }
    }

    /**
     * Set cache lifetime to timestamp of next element
     *
     * @param int $outputTimestamp
     * @param array $nextElement
     */
    private function updateOutputCacheLifetime($outputTimestamp, $nextElement)
    {
        $cacheService = \Pimcore::getContainer()->get(FullPageCacheListener::class);

        if ($cacheService->isEnabled()) {
            $calculatedLifetime = $nextElement['date'] - $outputTimestamp;
            $currentLifetime = $cacheService->getLifetime();

            if (empty($currentLifetime) || $currentLifetime > $calculatedLifetime) {
                $cacheService->setLifetime($calculatedLifetime);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loop()
    {
        $this->setDefault();
        $elements = $this->filterElements();

        if (empty($elements)) {
            return false;
        }

        if ($this->current > 0) {
            $this->blockDestruct();
            $this->blockEnd();
        } else {
            $this->start();
        }

        if ($this->current < count($elements) && $this->current < $this->config['limit']) {
            $this->blockConstruct();
            $this->blockStart();

            return true;
        } else {
            $this->end();

            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        if ($this->getEditmode()) {
            // this is actually to add the block to the EditmodeEditableDefinitionCollector
            // because for the block editables __toString() is never called
            $this->render();
        }

        // set name suffix for the whole block element, this will be added to all child elements of the block
        $this->getBlockState()->pushBlock(BlockName::createFromEditable($this));

        $attributes = $this->getEditmodeElementAttributes();
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $this->outputEditmode('<div ' . $attributeString . '>');

        $this->outputEditmode('<div class="pimcore_scheduled_block_controls" ></div>');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function blockConstruct()
    {
        // set the current block suffix for the child elements (0, 1, 3, ...)
        // this will be removed in blockDestruct
        $elements = $this->filterElements();

        $this->getBlockState()->pushIndex($elements[$this->current]['key']);
    }

    /**
     * {@inheritdoc}
     */
    public function blockStart($showControls = true, $return = false)
    {
        $attributes = [
            'data-name' => $this->getName(),
            'data-real-name' => $this->getRealName(),
        ];

        $outerAttributes = [
            'key' => $this->indices[$this->current]['key'],
            'date' => $this->indices[$this->current]['date'],
        ];

        $attr = HtmlUtils::assembleAttributeString($attributes);
        $oAttr = HtmlUtils::assembleAttributeString($outerAttributes);

        // outer element
        $this->outputEditmode('<div class="pimcore_block_entry" ' . $oAttr . ' ' . $attr . '>');

        $this->current++;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentIndex()
    {
        return $this->indices[$this->getCurrent()]['key'];
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        while ($this->loop()) {
            yield $this->getCurrentIndex();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getElements()
    {
        $document = $this->getDocument();

        $parentBlockNames = $this->getParentBlockNames();
        $parentBlockNames[] = $this->getName();

        $list = [];
        foreach ($this->getData() as $index) {
            $list[] = new Block\Item($document, $parentBlockNames, (int)$index['key']);
        }

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($config)
    {
        $config['reload'] = true;
        parent::setConfig($config);

        return $this;
    }

    /**
     * If object was serialized, set cached elements to null
     */
    public function __wakeup()
    {
        parent::__wakeup();
        $this->cachedCurrentElement = null;
    }
}
