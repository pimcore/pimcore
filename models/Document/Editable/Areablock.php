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

namespace Pimcore\Model\Document\Editable;

use Exception;
use Generator;
use Pimcore;
use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\EditableHandler;
use Pimcore\Extension\Document\Areabrick\AreabrickManagerInterface;
use Pimcore\Extension\Document\Areabrick\EditableDialogBoxInterface;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Templating\Renderer\EditableRenderer;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Areablock extends Model\Document\Editable implements BlockInterface
{
    /**
     * Contains an array of indices, which represent the order of the elements in the block
     *
     * @internal
     *
     */
    protected array $indices = [];

    /**
     * Current step of the block while iteration
     *
     * @internal
     *
     */
    protected int $current = 0;

    /**
     * @internal
     *
     */
    protected ?array $currentIndex = null;

    /**
     * @internal
     *
     */
    protected ?bool $blockStarted = false;

    /**
     * @internal
     *
     */
    protected array $brickTypeUsageCounter = [];

    public function getType(): string
    {
        return 'areablock';
    }

    public function getData(): mixed
    {
        return $this->indices;
    }

    public function admin(): void
    {
        $this->frontend();
    }

    public function frontend(): void
    {
        reset($this->indices);
        while ($this->loop());
    }

    /**
     * @internal
     *
     * @return ($return is true ? string : void)
     */
    public function renderIndex(int $index, bool $return = false)
    {
        $this->start($return);

        $this->currentIndex = $this->indices[$index];
        $this->current = $index;

        $this->blockConstruct();
        $templateParams = $this->blockStart();

        $content = $this->content(null, $templateParams, $return);
        if (!$return) {
            echo $content;
        }

        $this->blockDestruct();
        $this->blockEnd();
        $this->end($return);

        if ($return) {
            return $content;
        }
    }

    public function getIterator(): Generator
    {
        while ($this->loop()) {
            yield $this->getCurrentIndex();
        }
    }

    /**
     * @internal
     *
     */
    public function loop(): bool
    {
        $disabled = false;
        $config = $this->getConfig();
        $manual = (($config['manual'] ?? false) == true);

        if ($this->current > 0) {
            if (!$manual && $this->blockStarted) {
                $this->blockDestruct();
                $this->blockEnd();
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

            $this->blockStarted = false;
            $info = $this->buildInfoObject();

            if (!$manual && !$disabled) {
                $this->blockConstruct();
                $templateParams = $this->blockStart($info);

                $this->content($info, $templateParams);
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

    /**
     * @internal
     *
     */
    public function buildInfoObject(): Area\Info
    {
        $config = $this->getConfig();
        // create info object and assign it to the view
        $info = new Area\Info();
        $info->setId($this->currentIndex ? $this->currentIndex['type'] : null);
        $info->setEditable($this);
        $info->setIndex($this->current);

        $params = [];
        if (is_array($config['params'][$this->currentIndex['type']] ?? null)) {
            $params = $config['params'][$this->currentIndex['type']];
        }

        if (is_array($config['globalParams'] ?? null)) {
            $params = array_merge($config['globalParams'], $params);
        }

        $info->setParams($params);

        return $info;
    }

    /**
     * @param null|Document\Editable\Area\Info $info
     *
     * @return string|void
     */
    public function content(Area\Info $info = null, array $templateParams = [], bool $return = false)
    {
        if (!$info) {
            $info = $this->buildInfoObject();
        }

        $content = '';

        if ($this->editmode || !isset($this->currentIndex['hidden']) || !$this->currentIndex['hidden']) {
            $templateParams['isAreaBlock'] = true;
            $content = $this->getEditableHandler()->renderAreaFrontend($info, $templateParams);
            if (!$return) {
                echo $content;
            }
            $this->brickTypeUsageCounter += [$this->currentIndex['type'] => 0];
            $this->brickTypeUsageCounter[$this->currentIndex['type']]++;
        }

        $this->current++;

        if ($return) {
            return $content;
        }
    }

    /**
     * @internal
     *
     */
    protected function getEditableHandler(): EditableHandler
    {
        // TODO inject area handler via DI when editables are built through container
        return Pimcore::getContainer()->get(EditableHandler::class);
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->indices = $unserializedData;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->indices = $data;

        return $this;
    }

    public function blockConstruct(): void
    {
        // set the current block suffix for the child elements (0, 1, 3, ...)
        // this will be removed in blockDestruct
        $this->getBlockState()->pushIndex($this->indices[$this->current]['key']);
    }

    public function blockDestruct(): void
    {
        $this->getBlockState()->popIndex();
    }

    private function getToolBarDefaultConfig(): array
    {
        return [
            'areablock_toolbar' => [
                'width' => 172,
                'buttonWidth' => 168,
                'buttonMaxCharacters' => 20,
            ],
        ];
    }

    public function getEditmodeDefinition(): array
    {
        $config = array_merge($this->getToolBarDefaultConfig(), $this->getConfig());

        $options = parent::getEditmodeDefinition();
        $options = array_merge($options, [
            'config' => $config,
        ]);

        return $options;
    }

    protected function getEditmodeElementAttributes(): array
    {
        $attributes = parent::getEditmodeElementAttributes();

        $attributes = array_merge($attributes, [
            'name' => $this->getName(),
            'type' => $this->getType(),
        ]);

        return $attributes;
    }

    public function start(bool $return = false)
    {
        if (($this->config['manual'] ?? false) === true) {
            // in manual mode $this->render() is not called for the areablock, so we need to add
            // the editable to the collector manually here
            if ($editableDefCollector = $this->getEditableDefinitionCollector()) {
                $editableDefCollector->add($this);
            }
        }

        reset($this->indices);

        // set name suffix for the whole block element, this will be added to all child elements of the block
        $this->getBlockState()->pushBlock(BlockName::createFromEditable($this));

        $attributes = $this->getEditmodeElementAttributes();
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $html = '<div ' . $attributeString . '>';

        if ($return) {
            return $html;
        }

        $this->outputEditmode($html);

        return $this;
    }

    public function end(bool $return = false)
    {
        $this->current = 0;

        // remove the current block which was set by $this->start()
        $this->getBlockState()->popBlock();

        $html = '</div>';

        if ($return) {
            return $html;
        }

        $this->outputEditmode($html);
    }

    public function blockStart(Area\Info $info = null): array
    {
        $this->blockStarted = true;
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

        $areabrickManager = Pimcore::getContainer()->get(AreabrickManagerInterface::class);

        $dialogConfig = null;
        $brick = $areabrickManager->getBrick($this->indices[$this->current]['type']);
        if ($this->getEditmode() && $brick instanceof EditableDialogBoxInterface) {
            $dialogConfig = $brick->getEditableDialogBoxConfiguration($this, $info);
            if ($dialogConfig->getItems()) {
                $dialogConfig->setId('dialogBox-' . $this->getName() . '-' . $this->indices[$this->current]['key']);
            } else {
                $dialogConfig = null;
            }
        }

        $attr = HtmlUtils::assembleAttributeString($attributes);
        $oAttr = HtmlUtils::assembleAttributeString($outerAttributes);

        $dialogAttributes = '';
        if ($dialogConfig) {
            $dialogAttributes = HtmlUtils::assembleAttributeString([
                'data-dialog-id' => $dialogConfig->getId(),
            ]);
        }

        $dialogHtml = '';
        if ($dialogConfig) {
            $editableRenderer = Pimcore::getContainer()->get(EditableRenderer::class);
            $items = $this->renderDialogBoxEditables($dialogConfig->getItems(), $editableRenderer, $dialogConfig->getId(), $dialogHtml);
            $dialogConfig->setItems($items);
        }

        return [
            'editmodeOuterAttributes' => $oAttr,
            'editmodeGenericAttributes' => $attr,
            'editableDialog' => $dialogConfig,
            'editableDialogAttributes' => $dialogAttributes,
            'dialogHtml' => $dialogHtml,
        ];
    }

    /**
     * This method needs to be `protected` as it is used in other bundles such as pimcore/headless-documents
     *
     *
     * @throws Exception
     *
     * @internal
     */
    protected function renderDialogBoxEditables(array|Document\Editable $config, EditableRenderer $editableRenderer, string $dialogId, string &$html): array
    {
        if ($config instanceof BlockInterface || $config instanceof Area) {
            // Unsupported element was passed (e.g., Block, Areablock, ...)
            // or an Areas was passed, which is not supported to avoid too long editable names
            throw new Exception(sprintf('Using editables of type "%s" for the editable dialog "%s" is not supported.', get_debug_type($config), $dialogId));
        } elseif ($config instanceof Document\Editable) {
            // Map editable to array config
            $config = [
                'type' => $config->getType(),
                'name' => $config->getName(),
                'label' => $config->getLabel(),
                'config' => $config->getConfig(),
                'description' => $config->getDialogDescription(),
            ];
        }

        if (isset($config['items']) && is_array($config['items'])) {
            // layout component
            foreach ($config['items'] as $index => $child) {
                $config['items'][$index] = $this->renderDialogBoxEditables($child, $editableRenderer, $dialogId, $html);
            }
        } elseif (isset($config['name']) && isset($config['type'])) {
            $editable = $editableRenderer->getEditable($this->getDocument(), $config['type'], $config['name'], $config['config'] ?? []);
            if (!$editable instanceof Document\Editable) {
                throw new Exception(sprintf('Invalid editable type "%s" configured for Dialog Box', $config['type']));
            }

            $editable->setInDialogBox($dialogId);
            $editable->addConfig('dialogBoxConfig', $config);
            $html .= $editable->render();
        } else {
            foreach ($config as $index => $item) {
                $config['items'][$index] = $this->renderDialogBoxEditables($item, $editableRenderer, $dialogId, $html);
            }
        }

        return $config;
    }

    public function blockEnd(): void
    {
        $this->blockStarted = false;
    }

    public function setConfig(array $config): static
    {
        // we need to set this here otherwise custom areaDir's won't work
        $this->config = $config;

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
                $groups[$n] = $uncatAreas;
            }

            $config['group'] = $groups;
        }

        if (empty($config['limit'])) {
            $config['limit'] = 1000000;
        }

        $config['blockStateStack'] = json_encode($this->getBlockStateStack());

        $this->config = $config;

        if (($this->config['manual'] ?? false) === true) {
            $this->config['reload'] = true;
        }

        return $this;
    }

    /**
     * Sorts areas by index (sorting option) first, then by name
     */
    private function sortAvailableAreas(array $areas, array $config): array
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

    public function getCount(): int
    {
        return count($this->indices);
    }

    public function getCurrent(): int
    {
        return $this->current - 1;
    }

    public function getCurrentIndex(): int
    {
        return $this->indices[$this->getCurrent()]['key'] ?? 0;
    }

    public function getIndices(): array
    {
        return $this->indices;
    }

    /**
     * If object was serialized, set the counter back to 0
     */
    public function __wakeup(): void
    {
        $this->current = 0;
        reset($this->indices);
    }

    public function isEmpty(): bool
    {
        return !(bool) count($this->indices);
    }

    /**
     *
     * @return Areablock\Item[]
     */
    public function getElement(string $name): array
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
