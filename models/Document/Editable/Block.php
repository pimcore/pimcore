<?php
declare(strict_types=1);

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

use Generator;
use Pimcore;
use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Model;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Block extends Model\Document\Editable implements BlockInterface
{
    /**
     * @internal
     */
    const ATTRIBUTE_IGNORE_EDITMODE_INDICES = '_block_ignore_extra_editmode_indices';

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

    public function getType(): string
    {
        return 'block';
    }

    public function getData(): mixed
    {
        return $this->indices;
    }

    public function admin()
    {
        // nothing to do
        return '';
    }

    public function frontend()
    {
        // nothing to do
        return '';
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

    /**
     * @internal
     *
     * @return $this
     */
    protected function setDefault(): static
    {
        if (empty($this->indices) && isset($this->config['default']) && $this->config['default']) {
            for ($i = 0; $i < (int)$this->config['default']; $i++) {
                $this->indices[$i] = $i + 1;
            }
        }

        return $this;
    }

    public function getIterator(): Generator
    {
        while ($this->loop()) {
            yield $this->getCurrentIndex();
        }

        if ($this->getEditmode() && !$this->isIgnoreEditmodeIndices()) {
            // yeah, I know the following is f******* crazy :D
            $this->current = 0;
            $indicesBackup = $this->indices;
            $this->indices[0] = 1000000;
            $this->getBlockState()->pushBlock(BlockName::createFromEditable($this));
            $this->blockConstruct();
            $blockStartHtml = $this->blockStart(true, true);
            ob_start();

            $editableDefCollector = $this->getEditableDefinitionCollector();
            $editableDefCollector->stashPush();

            yield $this->getCurrentIndex() + 1;

            $blockEndHtml = $this->blockEnd(true);
            $this->blockDestruct();
            $blockState = $this->getBlockState();
            if ($blockState->hasBlocks()) {
                $blockState->popBlock();
            }

            $templateEditableDefinitions = $editableDefCollector->getDefinitions();
            $editableDefCollector->stashPull();

            $this->config['template'] = [
                'html' => $blockStartHtml . ob_get_clean() . $blockEndHtml,
                'editables' => $templateEditableDefinitions,
            ];

            $editableDefCollector->add($this);

            $this->indices = $indicesBackup;
        }
    }

    /**
     * @internal
     *
     */
    public function loop(): bool
    {
        $manual = false;
        if (($this->config['manual'] ?? false) == true) {
            $manual = true;
        }

        $this->setDefault();

        if ($this->current > 0) {
            if (!$manual) {
                $this->blockDestruct();
                $this->blockEnd();
            }
        } else {
            if (!$manual) {
                $this->start();
            }
        }

        if ($this->current < count($this->indices) && $this->current < $this->config['limit']) {
            if (!$manual) {
                $this->blockConstruct();
                $this->blockStart();
            }

            return true;
        } else {
            if (!$manual) {
                $this->end();
            }

            return false;
        }
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

    public function start()
    {
        // set name suffix for the whole block element, this will be added to all child elements of the block
        $this->getBlockState()->pushBlock(BlockName::createFromEditable($this));

        $attributes = $this->getEditmodeElementAttributes();
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $this->outputEditmode('<div ' . $attributeString . '>');

        return $this;
    }

    public function end(bool $return = false): void
    {
        $this->current = 0;

        // remove the current block which was set by $this->start()
        $blockState = $this->getBlockState();
        if ($blockState->hasBlocks()) {
            $blockState->popBlock();
        }

        $this->outputEditmode('</div>');
    }

    public function blockConstruct(): void
    {
        // set the current block suffix for the child elements (0, 1, 3, ...)
        // this will be removed in blockDestruct
        $this->getBlockState()->pushIndex((int) ($this->indices[$this->current] ?? 0));
    }

    public function blockDestruct(): void
    {
        $blockState = $this->getBlockState();
        if ($blockState->hasIndexes()) {
            $blockState->popIndex();
        }
    }

    public function blockStart(bool $showControls = true, bool $return = false, string $additionalClass = '')
    {
        $attr = $this->getBlockAttributes();

        $outerAttributes = [
            'key' => $this->indices[$this->current] ?? null,
        ];
        $oAttr = HtmlUtils::assembleAttributeString($outerAttributes);

        $class = 'pimcore_block_entry';
        if (!empty($additionalClass)) {
            $class = sprintf('%s %s', $class, $additionalClass);
        }

        $html = '<div class="' . $class . '" ' . $oAttr . ' ' . $attr . '>';

        if ($showControls) {
            $html .= $this->blockControls(true);
        }

        if ($return) {
            return $html;
        }

        $this->outputEditmode($html);
    }

    /**
     * Custom position of button controls between blockStart -> blockEnd
     *
     * @return ($return is true ? string : void)
     */
    public function blockControls(bool $return = false)
    {
        $attr = $this->getBlockAttributes();

        $html = <<<EOT
<div class="pimcore_block_buttons" $attr>
    <div class="pimcore_block_amount" $attr></div>
    <div class="pimcore_block_plus" $attr></div>
    <div class="pimcore_block_minus" $attr></div>
    <div class="pimcore_block_up" $attr></div>
    <div class="pimcore_block_down" $attr></div>
    <div class="pimcore_block_clear" $attr></div>
</div>
EOT;

        $this->current++;

        if ($return) {
            return $html;
        }

        $this->outputEditmode($html);
    }

    public function blockEnd(bool $return = false)
    {
        // close outer element
        $html = '</div>';

        if ($return) {
            return $html;
        }

        $this->outputEditmode($html);
    }

    public function setConfig(array $config): static
    {
        if (empty($config['limit'])) {
            $config['limit'] = 1000000;
        }

        $this->config = $config;

        if (($this->config['manual'] ?? false) === true) {
            $this->config['reload'] = true;
        }

        return $this;
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
        return (int) ($this->indices[$this->getCurrent()] ?? 0);
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
    }

    public function isEmpty(): bool
    {
        return !(bool) count($this->indices);
    }

    /**
     * @return Block\Item[]
     */
    public function getElements(): array
    {
        $document = $this->getDocument();

        // https://github.com/pimcore/pimcore/issues/6629
        if (!$document instanceof Model\Document\PageSnippet) {
            return [];
        }

        $parentBlockNames = $this->getParentBlockNames();
        $parentBlockNames[] = $this->getName();

        $list = [];
        foreach ($this->getData() as $index) {
            $list[] = new Block\Item($document, $parentBlockNames, (int)$index);
        }

        return $list;
    }

    private function getBlockAttributes(): string
    {
        $attributes = [
            'data-name' => $this->getName(),
            'data-real-name' => $this->getRealName(),
        ];

        return HtmlUtils::assembleAttributeString($attributes);
    }

    private function isIgnoreEditmodeIndices(): bool
    {
        $requestStack = Pimcore::getContainer()->get('request_stack');
        $request = $requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        return $request->attributes->getBoolean(self::ATTRIBUTE_IGNORE_EDITMODE_INDICES);
    }
}
