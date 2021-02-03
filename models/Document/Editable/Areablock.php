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

namespace Pimcore\Model\Document\Editable;

use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\EditableHandlerInterface;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Tool;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Areablock extends Model\Document\Editable implements BlockInterface
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
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'areablock';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->indices;
    }

    /**
     * @see EditableInterface::admin
     *
     * @return void
     */
    public function admin()
    {
        $this->frontend();
    }

    /**
     * @see EditableInterface::frontend
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
        $config = $this->getConfig();
        $manual = (($config['manual'] ?? false) == true);

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

        if ($this->current < count($this->indices) && $this->current < $config['limit']) {
            $index = current($this->indices);
            next($this->indices);

            $this->currentIndex = $index;
            if (!empty($config['allowed']) && !in_array($index['type'], $config['allowed'])) {
                $disabled = true;
            }

            $brickTypeLimit = $config['limits'][$this->currentIndex['type']] ?? 100000;
            $brickTypeUsageCounter = $this->brickTypeUsageCounter[$this->currentIndex['type']] ?? 0;
            if ($brickTypeUsageCounter >= $brickTypeLimit) {
                $disabled = true;
            }

            if (!$this->getEditableHandler()->isBrickEnabled($this, $index['type']) && $config['dontCheckEnabled'] != true) {
                $disabled = true;
            }

            $this->blockStarted = false;
            $info = $this->buildInfoObject();

            if (!$manual && !$disabled) {
                $this->blockConstruct();
                $this->blockStart($info);

                $this->blockStarted = true;
                $this->content($info);
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

    protected function buildInfoObject(): Area\Info
    {
        // create info object and assign it to the view
        $info = new Area\Info();
        try {
            $info->setId($this->currentIndex['type']);
            $info->setEditable($this);
            $info->setIndex($this->current);
        } catch (\Exception $e) {
            Logger::err($e);
        }

        $params = [];

        $config = $this->getConfig();
        if (isset($config['params']) && is_array($config['params']) && array_key_exists($this->currentIndex['type'], $config['params'])) {
            if (is_array($config['params'][$this->currentIndex['type']])) {
                $params = $config['params'][$this->currentIndex['type']];
            }
        }

        if (isset($config['globalParams'])) {
            $params = array_merge($config['globalParams'], (array)$params);
        }

        $info->setParams($params);

        return $info;
    }

    public function content($info = null)
    {
        if (!$info) {
            $info = $this->buildInfoObject();
        }

        if ($this->editmode || !isset($this->currentIndex['hidden']) || !$this->currentIndex['hidden']) {
            $this->getEditableHandler()->renderAreaFrontend($info);
            $this->brickTypeUsageCounter += [$this->currentIndex['type'] => 0];
            $this->brickTypeUsageCounter[$this->currentIndex['type']]++;
        }

        $this->current++;
    }

    /**
     * @return EditableHandlerInterface
     */
    private function getEditableHandler()
    {
        // TODO inject area handler via DI when editables are built through container
        return \Pimcore::getContainer()->get(EditableHandlerInterface::class);
    }

    /**
     * @see EditableInterface::setDataFromResource
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
     * @see EditableInterface::setDataFromEditmode
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
        $config = array_merge($this->getToolBarDefaultConfig(), $this->getConfig());

        $options = parent::getEditmodeOptions();
        $options = array_merge($options, [
            'config' => $config,
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
        $this->getBlockState()->pushBlock(BlockName::createFromEditable($this));

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
     * Is called everytime a new iteration starts (new entry of the block while looping)
     *
     * @param null $info
     */
    public function blockStart($info = null)
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

        $areabrickManager = \Pimcore::getContainer()->get(AreabrickManagerInterface::class);

        $dialogConfig = null;
        $brick = $areabrickManager->getBrick($this->indices[$this->current]['type']);
        if ($this->getEditmode() && $brick instanceof EditableDialogBoxInterface) {
            $dialogConfig = $brick->getEditableDialogBoxConfiguration($this, $info);
            $dialogConfig->setId('dialogBox-' . $this->getName() . '-' . $this->indices[$this->current]['key']);
        }

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

        if ($dialogConfig) {
            $dialogAttributes = [
                'data-dialog-id' => $dialogConfig->getId(),
            ];

            $dialogAttributes = HtmlUtils::assembleAttributeString($dialogAttributes);

            $this->outputEditmode('<div class="pimcore_block_dialog" ' . $attr . ' ' . $dialogAttributes . '></div>');
        }

        $this->outputEditmode('<div class="pimcore_block_label" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_clear" ' . $attr . '></div>');

        $this->outputEditmode('</div>'); // .pimcore_area_buttons_inner
        $this->outputEditmode('</div>'); // .pimcore_area_buttons

        if ($dialogConfig) {
            $editableRenderer = \Pimcore::getContainer()->get(EditableRenderer::class);
            $this->outputEditmode('<template id="dialogBoxConfig-' . $dialogConfig->getId() . '">' . \htmlspecialchars(\json_encode($dialogConfig)) . '</template>');
            $this->renderDialogBoxEditables($dialogConfig->getItems(), $editableRenderer, $dialogConfig->getId());
        }
    }

    /**
     * @param array $config
     * @param EditableRenderer $editableRenderer
     * @param string $dialogId
     */
    private function renderDialogBoxEditables(array $config, EditableRenderer $editableRenderer, string $dialogId)
    {
        if (isset($config['items']) && is_array($config['items'])) {
            // layout component
            foreach ($config['items'] as $child) {
                $this->renderDialogBoxEditables($child, $editableRenderer, $dialogId);
            }
        } elseif (isset($config['name']) && isset($config['type'])) {
            $editable = $editableRenderer->getEditable($this->getDocument(), $config['type'], $config['name'], $config['config'] ?? []);
            if (!$editable instanceof Document\Editable) {
                throw new \Exception(sprintf('Invalid editable type "%s" configured for Dialog Box', $config['type']));
            }

            $editable->setInDialogBox($dialogId);
            $editable->setOption('dialogBoxConfig', $config);
            $this->outputEditmode($editable->admin());
        } elseif (is_array($config) && isset($config[0])) {
            foreach ($config as $item) {
                $this->renderDialogBoxEditables($item, $editableRenderer, $dialogId);
            }
        }
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
     * @param array $config
     *
     * @return $this
     */
    public function setConfig($config)
    {
        // we need to set this here otherwise custom areaDir's won't work
        $this->config = $config;

        if ($this->getView()) {
            $translator = \Pimcore::getContainer()->get('translator');
            if (!isset($config['allowed']) || !is_array($config['allowed'])) {
                $config['allowed'] = [];
            }

            $availableAreas = $this->getEditableHandler()->getAvailableAreablockAreas($this, $config);
            $availableAreas = $this->sortAvailableAreas($availableAreas, $config);

            $config['types'] = $availableAreas;

            if (isset($config['group']) && is_array($config['group'])) {
                $groupingareas = [];
                foreach ($availableAreas as $area) {
                    $groupingareas[$area['type']] = $area['type'];
                }

                $groups = [];
                foreach ($config['group'] as $name => $areas) {
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

                $config['group'] = $groups;
            }

            if (empty($config['limit'])) {
                $config['limit'] = 1000000;
            }

            $this->config = $config;
        }

        return $this;
    }

    /**
     * Sorts areas by index (sorting option) first, then by name
     *
     * @param array $areas
     * @param array $config
     *
     * @return array
     */
    protected function sortAvailableAreas(array $areas, array $config)
    {
        if (isset($config['sorting']) && is_array($config['sorting']) && count($config['sorting'])) {
            $sorting = $config['sorting'];
        } else {
            if (isset($config['allowed']) && is_array($config['allowed']) && count($config['allowed'])) {
                $sorting = $config['allowed'];
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

class_alias(Areablock::class, 'Pimcore\Model\Document\Tag\Areablock');
