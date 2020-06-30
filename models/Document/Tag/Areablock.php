<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @category   Pimcore
 * @package    Document
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Document\Tag;

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\TagHandlerInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Tool;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 */
class Areablock extends Model\Document\Tag implements BlockInterface
{
    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @var array
     */
    public $indices = [];

    /**
     * Current step of the block while iteration
     *
     * @var int
     */
    public $current = 0;

    /**
     * @var array
     */
    public $currentIndex;

    /**
     * @var bool
     */
    protected $blockStarted;

    /**
     * @var array
     */
    private $brickTypeUsageCounter = [];

    /**
     * @see Document\Tag\TagInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'areablock';
    }

    /**
     * @see Document\Tag\TagInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->indices;
    }

    /**
     * @see Document\Tag\TagInterface::admin
     *
     * @return void
     */
    public function admin()
    {
        $this->frontend();
    }

    /**
     * @see Document\Tag\TagInterface::frontend
     *
     * @return void
     */
    public function frontend()
    {
        if (!is_array($this->indices)) {
            $this->indices = [];
        }
        reset($this->indices);
        while ($this->loop());
    }

    /**
     * @param int $index
     */
    public function renderIndex($index)
    {
        $this->start();

        $this->currentIndex = $this->indices[$index];
        $this->current = $index;

        $this->blockConstruct();
        $this->blockStart();

        $this->content();

        $this->blockDestruct();
        $this->blockEnd();
        $this->end();
    }

    public function loop()
    {
        $disabled = false;
        $options = $this->getOptions();
        $manual = false;
        if (is_array($options) && array_key_exists('manual', $options) && $options['manual'] == true) {
            $manual = true;
        }

        if ($this->current > 0) {
            if (!$manual && $this->blockStarted) {
                $this->blockDestruct();
                $this->blockEnd();

                $this->blockStarted = false;
            }
        } else {
            if (!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->options['limit']) {
            $index = current($this->indices);
            next($this->indices);

            $this->currentIndex = $index;
            if (!empty($options['allowed']) && !in_array($index['type'], $options['allowed'])) {
                $disabled = true;
            }

            $brickTypeLimit = $this->options['limits'][$this->currentIndex['type']] ?? 100000;
            $brickTypeUsageCounter = $this->brickTypeUsageCounter[$this->currentIndex['type']] ?? 0;
            if ($brickTypeUsageCounter >= $brickTypeLimit) {
                $disabled = true;
            }

            if (!$this->getTagHandler()->isBrickEnabled($this, $index['type']) && $options['dontCheckEnabled'] != true) {
                $disabled = true;
            }

            $this->blockStarted = false;

            if (!$manual && !$disabled) {
                $this->blockConstruct();
                $this->blockStart();

                $this->blockStarted = true;
                $this->content();
            } elseif (!$manual) {
                $this->current++;
            }

            return true;
        } else {
            if (!$manual) {
                $this->end();
            }

            return false;
        }
    }

    public function content()
    {
        // create info object and assign it to the view
        $info = new Area\Info();
        try {
            $info->setId($this->currentIndex['type']);
            $info->setTag($this);
            $info->setIndex($this->current);
        } catch (\Exception $e) {
            Logger::err($e);
        }

        $params = [];

        $options = $this->getOptions();
        if (isset($options['params']) && is_array($options['params']) && array_key_exists($this->currentIndex['type'], $options['params'])) {
            if (is_array($options['params'][$this->currentIndex['type']])) {
                $params = $options['params'][$this->currentIndex['type']];
            }
        }

        if (isset($options['globalParams'])) {
            $params = array_merge($options['globalParams'], (array)$params);
        }

        $info->setParams($params);

        if ($this->editmode || !isset($this->currentIndex['hidden']) || !$this->currentIndex['hidden']) {
            $this->getTagHandler()->renderAreaFrontend($info);
            $this->brickTypeUsageCounter += [$this->currentIndex['type'] => 0];
            $this->brickTypeUsageCounter[$this->currentIndex['type']]++;
        }

        $this->current++;
    }

    /**
     * @return TagHandlerInterface
     */
    private function getTagHandler()
    {
        // TODO inject area handler via DI when tags are built through container
        return \Pimcore::getContainer()->get('pimcore.document.tag.handler');
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromResource
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->indices = Tool\Serialize::unserialize($data);
        if (!is_array($this->indices)) {
            $this->indices = [];
        }

        return $this;
    }

    /**
     * @see Document\Tag\TagInterface::setDataFromEditmode
     *
     * @param mixed $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->indices = $data;

        return $this;
    }

    /**
     * Called before the block is rendered
     */
    public function blockConstruct()
    {
        // set the current block suffix for the child elements (0, 1, 3, ...)
        // this will be removed in blockDestruct
        $this->getBlockState()->pushIndex($this->indices[$this->current]['key']);
    }

    /**
     * Called when the block was rendered
     */
    public function blockDestruct()
    {
        $this->getBlockState()->popIndex();
    }

    /**
     * @return array
     */
    protected function getToolBarDefaultConfig()
    {
        return [
            'areablock_toolbar' => [
                'width' => 172,
                'buttonWidth' => 168,
                'buttonMaxCharacters' => 20,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getEditmodeOptions(): array
    {
        $configOptions = array_merge($this->getToolBarDefaultConfig(), $this->getOptions());

        $options = parent::getEditmodeOptions();
        $options = array_merge($options, [
            'options' => $configOptions,
        ]);

        return $options;
    }

    /**
     * @inheritDoc
     */
    protected function getEditmodeElementAttributes(array $options): array
    {
        $attributes = parent::getEditmodeElementAttributes($options);

        $attributes = array_merge($attributes, [
            'name' => $this->getName(),
            'type' => $this->getType(),
        ]);

        return $attributes;
    }

    /**
     * Is executed at the beginning of the loop and setup some general settings
     *
     * @return $this
     */
    public function start()
    {
        reset($this->indices);

        $options = $this->getEditmodeOptions();
        $this->outputEditmodeOptions($options);

        // set name suffix for the whole block element, this will be added to all child elements of the block
        $this->getBlockState()->pushBlock(BlockName::createFromTag($this));

        $attributes = $this->getEditmodeElementAttributes($options);
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $this->outputEditmode('<div ' . $attributeString . '>');

        return $this;
    }

    /**
     * Is executed at the end of the loop and removes the settings set in start()
     */
    public function end()
    {
        $this->current = 0;

        // remove the current block which was set by $this->start()
        $this->getBlockState()->popBlock();

        $this->outputEditmode('</div>');
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     */
    public function blockStart()
    {
        $attributes = [
            'data-name' => $this->getName(),
            'data-real-name' => $this->getRealName(),
        ];

        $hidden = 'false';
        if (isset($this->indices[$this->current]['hidden']) && $this->indices[$this->current]['hidden']) {
            $hidden = 'true';
        }

        $outerAttributes = [
            'key' => $this->indices[$this->current]['key'],
            'type' => $this->indices[$this->current]['type'],
            'data-hidden' => $hidden,
        ];

        $attr = HtmlUtils::assembleAttributeString($attributes);
        $oAttr = HtmlUtils::assembleAttributeString($outerAttributes);

        // outer element
        $this->outputEditmode('<div class="pimcore_area_entry pimcore_block_entry" ' . $oAttr . ' ' . $attr . '>');

        $this->outputEditmode('<div class="pimcore_area_buttons" ' . $attr . '>');
        $this->outputEditmode('<div class="pimcore_area_buttons_inner">');

        $this->outputEditmode('<div class="pimcore_block_plus_up" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_plus" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_minus" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_up" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_down" ' . $attr . '></div>');

        $this->outputEditmode('<div class="pimcore_block_type" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_options" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_visibility" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_label" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_clear" ' . $attr . '></div>');

        $this->outputEditmode('</div>'); // .pimcore_area_buttons_inner
        $this->outputEditmode('</div>'); // .pimcore_area_buttons
    }

    /**
     * Is called evertime a new iteration ends (new entry of the block while looping)
     */
    public function blockEnd()
    {
        // close outer element
        $this->outputEditmode('</div>');
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        // we need to set this here otherwise custom areaDir's won't work
        $this->options = $options;

        if ($this->getView()) {
            $translator = \Pimcore::getContainer()->get('translator');
            if (!isset($options['allowed']) || !is_array($options['allowed'])) {
                $options['allowed'] = [];
            }

            $availableAreas = $this->getTagHandler()->getAvailableAreablockAreas($this, $options);
            $availableAreas = $this->sortAvailableAreas($availableAreas, $options);

            $options['types'] = $availableAreas;

            if (isset($options['group']) && is_array($options['group'])) {
                $groupingareas = [];
                foreach ($availableAreas as $area) {
                    $groupingareas[$area['type']] = $area['type'];
                }

                $groups = [];
                foreach ($options['group'] as $name => $areas) {
                    $n = $name;
                    if ($this->editmode) {
                        $n = $translator->trans($name, [], 'admin');
                    }
                    $groups[$n] = $areas;

                    foreach ($areas as $area) {
                        unset($groupingareas[$area]);
                    }
                }

                if (count($groupingareas) > 0) {
                    $uncatAreas = [];
                    foreach ($groupingareas as $area) {
                        $uncatAreas[] = $area;
                    }
                    $n = 'Uncategorized';
                    if ($this->editmode) {
                        $n = $translator->trans($n, [], 'admin');
                    }
                    $groups[$n] = $uncatAreas;
                }

                $options['group'] = $groups;
            }

            if (empty($options['limit'])) {
                $options['limit'] = 1000000;
            }

            $this->options = $options;
        }

        return $this;
    }

    /**
     * Sorts areas by index (sorting option) first, then by name
     *
     * @param array $areas
     * @param array $options
     *
     * @return array
     */
    protected function sortAvailableAreas(array $areas, array $options)
    {
        if (isset($options['sorting']) && is_array($options['sorting']) && count($options['sorting'])) {
            $sorting = $options['sorting'];
        } else {
            if (isset($options['allowed']) && is_array($options['allowed']) && count($options['allowed'])) {
                $sorting = $options['allowed'];
            } else {
                $sorting = [];
            }
        }

        $result = [
            'name' => [],
            'index' => [],
        ];

        foreach ($areas as $area) {
            $sortIndex = false;
            if (!empty($sorting)) {
                $sortIndex = array_search($area['type'], $sorting);
            }

            $sortKey = 'name'; // allowed and sorting is not set || areaName is not in allowed
            if (false !== $sortIndex) {
                $sortKey = 'index';
                $area['sortIndex'] = $sortIndex;
            }

            $result[$sortKey][] = $area;
        }

        // sort with translated names
        if (count($result['name'])) {
            usort($result['name'], function ($a, $b) {
                if ($a['name'] == $b['name']) {
                    return 0;
                }

                return ($a['name'] < $b['name']) ? -1 : 1;
            });
        }

        // sort by allowed brick config order
        if (count($result['index'])) {
            usort($result['index'], function ($a, $b) {
                return $a['sortIndex'] - $b['sortIndex'];
            });
        }

        $result = array_merge($result['index'], $result['name']);

        return $result;
    }

    /**
     * Return the amount of block elements
     *
     * @return int
     */
    public function getCount()
    {
        return count($this->indices);
    }

    /**
     * Return current iteration step
     *
     * @return int
     */
    public function getCurrent()
    {
        return $this->current - 1;
    }

    /**
     * Return current index
     *
     * @return int
     */
    public function getCurrentIndex()
    {
        return $this->indices[$this->getCurrent()]['key'];
    }

    /**
     * If object was serialized, set the counter back to 0
     */
    public function __wakeup()
    {
        $this->current = 0;
        reset($this->indices);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !(bool) count($this->indices);
    }

    /**
     * @deprecated
     *
     * @param Model\Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     *
     * @throws \Exception
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        $data = $this->sanitizeWebserviceData($wsElement->value);
        if (($data->indices === null || is_array($data->indices)) && ($data->current == null || is_numeric($data->current))
            && ($data->currentIndex == null || is_numeric($data->currentIndex))) {
            $indices = $data->indices;
            $indices = json_decode(json_encode($indices), true);

            $this->indices = $indices;
            $this->current = $data->current;
            $this->currentIndex = $data->currentIndex;
        } else {
            throw new \Exception('cannot get  values from web service import - invalid data');
        }
    }

    /**
     * @param string $name
     *
     * @return Areablock\Item[]
     */
    public function getElement(string $name)
    {
        $document = $this->getDocument();

        $parentBlockNames = $this->getParentBlockNames();
        $parentBlockNames[] = $this->getName();

        $list = [];
        foreach ($this->getData() as $index => $item) {
            if ($item['type'] === $name) {
                $list[$index] = new Areablock\Item($document, $parentBlockNames, (int)$item['key']);
            }
        }

        return $list;
    }
}
