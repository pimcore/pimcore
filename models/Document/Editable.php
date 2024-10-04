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

namespace Pimcore\Model\Document;

use Exception;
use InvalidArgumentException;
use Pimcore;
use Pimcore\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\Block\BlockState;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\EditableNameEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Tool\HtmlUtils;
use Pimcore\Tool\Serialize;
use RuntimeException;
use Throwable;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 * @method void save()
 * @method void delete()
 */
abstract class Editable extends Model\AbstractModel implements Model\Document\Editable\EditableInterface
{
    /**
     * Contains some configurations for the editmode, or the thumbnail name, ...
     *
     * @internal
     */
    protected array $config = [];

    /**
     * The label rendered for the editmode dialog.
     *
     * @internal
     */
    protected ?string $label = null;

    /**
     * The description rendered for the editmode dialog.
     *
     * @internal
     */
    protected ?string $dialogDescription = null;

    /**
     * @internal
     *
     */
    protected string $name = '';

    /**
     * Contains the real name of the editable without the prefixes and suffixes
     * which are generated automatically by blocks and areablocks
     *
     * @internal
     */
    protected ?string $realName = '';

    /**
     * Contains parent hierarchy names (used when building elements inside a block/areablock hierarchy)
     *
     */
    private array $parentBlockNames = [];

    /**
     * Element belongs to the ID of the document
     *
     * @internal
     */
    protected ?int $documentId = null;

    /**
     * Element belongs to the document
     *
     * @internal
     */
    protected ?Document\PageSnippet $document = null;

    /**
     * In Editmode or not
     *
     * @internal
     */
    protected bool $editmode = false;

    /**
     * @internal
     */
    protected bool $inherited = false;

    /**
     * @internal
     */
    protected ?string $inDialogBox = null;

    private ?EditmodeEditableDefinitionCollector $editableDefinitionCollector = null;

    /**
     * @return string|void
     *
     * @throws Exception
     *
     * @internal
     */
    public function admin()
    {
        $attributes = $this->getEditmodeElementAttributes();
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $htmlContainerCode = ('<div ' . $attributeString . '></div>');

        if ($this->isInDialogBox()) {
            $htmlContainerCode = $this->wrapEditmodeContainerCodeForDialogBox($attributes['id'], $htmlContainerCode);
        }

        return $htmlContainerCode;
    }

    /**
     * Return the data for direct output to the frontend, can also contain HTML code!
     *
     * @return string|void
     */
    abstract public function frontend();

    private function wrapEditmodeContainerCodeForDialogBox(string $id, string $code): string
    {
        $code = '<template id="template__' . $id . '">' . $code . '</template>';

        return $code;
    }

    /**
     * Builds config passed to editmode frontend as JSON config
     *
     * @internal
     */
    public function getEditmodeDefinition(): array
    {
        $config = [
            // we don't use : and . in IDs (although it's allowed in HTML spec)
            // because they are used in CSS syntax and therefore can't be used in querySelector()
            'id' => 'pimcore_editable_' . str_replace([':', '.'], '_', $this->getName()),
            'name' => $this->getName(),
            'realName' => $this->getRealName(),
            'config' => $this->getConfig(),
            'data' => $this->getEditmodeData(),
            'type' => $this->getType(),
            'inherited' => $this->getInherited(),
            'inDialogBox' => $this->getInDialogBox(),
        ];

        return $config;
    }

    /**
     * Builds data used for editmode
     *
     * @internal
     */
    protected function getEditmodeData(): mixed
    {
        // get configuration data for admin
        if ($this instanceof Document\Editable\EditmodeDataInterface) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        return $data;
    }

    /**
     * Builds attributes used on the editmode HTML element
     *
     * @internal
     */
    protected function getEditmodeElementAttributes(): array
    {
        $config = $this->getEditmodeDefinition();

        if (!isset($config['id'])) {
            throw new RuntimeException(sprintf('Expected an "id" option to be set on the "%s" editable config array', $this->getName()));
        }

        $attributes = array_merge($this->getEditmodeBlockStateAttributes(), [
            'id' => $config['id'],
            'class' => implode(' ', $this->getEditmodeElementClasses()),
        ]);

        return $attributes;
    }

    /**
     * @internal
     */
    protected function getEditmodeBlockStateAttributes(): array
    {
        $blockState = $this->getBlockState();
        $blockNames = array_map(function (BlockName $blockName) {
            return $blockName->getRealName();
        }, $blockState->getBlocks());

        $attributes = [
            'data-name' => $this->getName(),
            'data-real-name' => $this->getRealName(),
            'data-type' => $this->getType(),
            'data-block-names' => implode(', ', $blockNames),
            'data-block-indexes' => implode(', ', $blockState->getIndexes()),
        ];

        return $attributes;
    }

    /**
     * Builds classes used on the editmode HTML element
     *
     * @internal
     */
    protected function getEditmodeElementClasses(): array
    {
        $classes = [
            'pimcore_editable',
            'pimcore_editable_' . $this->getType(),
        ];

        $editableConfig = $this->getConfig();
        if (isset($editableConfig['class'])) {
            if (is_array($editableConfig['class'])) {
                $classes = array_merge($classes, $editableConfig['class']);
            } else {
                $classes[] = (string)$editableConfig['class'];
            }
        }

        return $classes;
    }

    /**
     * Sends data to the output stream
     */
    protected function outputEditmode(string $value): void
    {
        if ($this->getEditmode()) {
            echo $value . "\n";
        }
    }

    public function getValue(): mixed
    {
        return $this->getData();
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function setDocumentId(int $id): static
    {
        $this->documentId = $id;

        if ($this->document instanceof PageSnippet && $this->document->getId() !== $this->documentId) {
            $this->document = null;
        }

        return $this;
    }

    public function getDocumentId(): ?int
    {
        return $this->documentId;
    }

    /**
     * @return $this
     */
    public function setDocument(Document\PageSnippet $document): static
    {
        $this->document = $document;
        $this->documentId = (int) $document->getId();

        return $this;
    }

    public function getDocument(): ?PageSnippet
    {
        if (!$this->document) {
            $this->document = Document\PageSnippet::getById($this->documentId);
        }

        return $this->document;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return $this
     */
    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @return $this
     */
    public function addConfig(string $name, mixed $value): static
    {
        $this->config[$name] = $value;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @return $this
     */
    public function setLabel(?string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDialogDescription(): ?string
    {
        return $this->dialogDescription;
    }

    /**
     * @return $this
     */
    public function setDialogDescription(?string $dialogDescription): static
    {
        $this->dialogDescription = $dialogDescription;

        return $this;
    }

    public function getRealName(): string
    {
        return $this->realName ?? '';
    }

    public function setRealName(string $realName): void
    {
        $this->realName = $realName;
    }

    final public function setParentBlockNames(array $parentNames): void
    {
        $this->parentBlockNames = $parentNames;
    }

    final public function getParentBlockNames(): array
    {
        return $this->parentBlockNames;
    }

    /**
     * Returns only the properties which should be serialized
     *
     */
    public function __sleep(): array
    {
        $finalVars = [];
        $parentVars = parent::__sleep();
        $blockedVars = ['editmode', 'parentBlockNames', 'document', 'config'];

        foreach ($parentVars as $key) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    public function __clone(): void
    {
        parent::__clone();
        $this->document = null;
    }

    final public function render(): mixed
    {
        if ($this->editmode) {
            if ($collector = $this->getEditableDefinitionCollector()) {
                $collector->add($this);
            }

            return $this->admin();
        }

        return $this->frontend();
    }

    /**
     * direct output to the frontend
     */
    public function __toString(): string
    {
        $result = '';

        try {
            $result = $this->render();
        } catch (Throwable $e) {
            if (Pimcore::inDebugMode()) {
                // the __toString method isn't allowed to throw exceptions
                $result = '<b style="color:#f00">' . $e->getMessage().' File: ' . $e->getFile().' Line: '. $e->getLine().'</b><br/>'.$e->getTraceAsString();

                return '<pre class="pimcore_editable_error">' . $result . '</pre>';
            }

            Logger::error('toString() returned an exception: {exception}', [
                'exception' => $e,
            ]);

            return '';
        }

        if (is_string($result) || is_numeric($result)) {
            // we have to cast to string, because int/float is not auto-converted and throws an exception
            return (string) $result;
        }

        return '';
    }

    public function getEditmode(): bool
    {
        return $this->editmode;
    }

    public function setEditmode(bool $editmode): static
    {
        $this->editmode = $editmode;

        return $this;
    }

    public function getDataForResource(): mixed
    {
        $this->checkValidity();

        return $this->getData();
    }

    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        return $tags;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     */
    public function resolveDependencies(): array
    {
        return [];
    }

    public function checkValidity(): bool
    {
        return true;
    }

    public function setInherited(bool $inherited): static
    {
        $this->inherited = $inherited;

        return $this;
    }

    public function getInherited(): bool
    {
        return $this->inherited;
    }

    /**
     * @internal
     */
    protected function getBlockState(): BlockState
    {
        return $this->getBlockStateStack()->getCurrentState();
    }

    /**
     * @internal
     */
    protected function getBlockStateStack(): BlockStateStack
    {
        return Pimcore::getContainer()->get(BlockStateStack::class);
    }

    /**
     * Builds an editable name for an editable, taking current
     * block state (block, index) and targeting into account.
     *
     * @internal
     *
     * @throws Exception
     */
    public static function buildEditableName(string $type, string $name, Document $document = null): string
    {
        // do NOT allow dots (.) and colons (:) here as they act as delimiters
        // for block hierarchy in the new naming scheme (see #1467)!
        if (!preg_match("@^[a-zA-Z0-9\-_]+$@", $name)) {
            throw new InvalidArgumentException(
                'Only valid CSS class selectors are allowed as the name for an editable (which is basically [a-zA-Z0-9\-_]+). Your name was: ' . $name
            );
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the editable is inside a block

        $container = Pimcore::getContainer();
        $blockState = $container->get(BlockStateStack::class)->getCurrentState();

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        $targetGroupEditableName = null;
        if ($document && interface_exists(TargetingDocumentInterface::class) && $document instanceof TargetingDocumentInterface) {
            $targetGroupEditableName = $document->getTargetGroupEditableName($name);

            if (!$blockState->hasBlocks()) {
                $name = $targetGroupEditableName;
            }
        }

        $editableName = self::doBuildName($name, $type, $blockState, $targetGroupEditableName);

        $event = new EditableNameEvent($type, $name, $blockState, $editableName, $document);
        Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::EDITABLE_NAME);

        $editableName = $event->getEditableName();

        if (strlen($editableName) > 750) {
            throw new Exception(sprintf(
                'Composite name for editable "%s" is longer than 750 characters. Use shorter names for your editables or reduce amount of nesting levels. Name is: %s',
                $name,
                $editableName
            ));
        }

        return $editableName;
    }

    private static function doBuildName(string $name, string $type, BlockState $blockState, ?string $targetGroupElementName = null): string
    {
        if (!$blockState->hasBlocks()) {
            return $name;
        }

        $blocks = $blockState->getBlocks();
        $indexes = $blockState->getIndexes();

        // check if the previous block is the name we're about to build
        // TODO: can this be avoided at the block level?
        if ($type === 'block' || $type == 'scheduledblock') {
            $tmpBlocks = $blocks;
            $tmpIndexes = $indexes;

            array_pop($tmpBlocks);
            array_pop($tmpIndexes);

            $tmpName = self::buildHierarchicalName($name, $tmpBlocks, $tmpIndexes);

            $previousBlockName = $blocks[count($blocks) - 1]->getName();
            if ($previousBlockName === $tmpName || ($targetGroupElementName && $previousBlockName === $targetGroupElementName)) {
                array_pop($blocks);
                array_pop($indexes);
            }
        }

        return self::buildHierarchicalName($name, $blocks, $indexes);
    }

    /**
     * @param BlockName[] $blocks
     * @param int[] $indexes
     */
    private static function buildHierarchicalName(string $name, array $blocks, array $indexes): string
    {
        if (count($indexes) > count($blocks)) {
            throw new RuntimeException(sprintf('Index count %d is greater than blocks count %d', count($indexes), count($blocks)));
        }

        $parts = [];
        for ($i = 0; $i < count($blocks); $i++) {
            $part = $blocks[$i]->getRealName();

            if (isset($indexes[$i])) {
                $part = sprintf('%s:%d', $part, $indexes[$i]);
            }

            $parts[] = $part;
        }

        $parts[] = $name;

        return implode('.', $parts);
    }

    /**
     * @internal
     *
     * @throws Exception
     */
    public static function buildChildEditableName(string $name, string $type, array $parentBlockNames, int $index): string
    {
        if (count($parentBlockNames) === 0) {
            throw new Exception(sprintf(
                'Failed to build child tag name for %s %s at index %d as no parent name was passed',
                $type,
                $name,
                $index
            ));
        }

        $parentName = array_pop($parentBlockNames);

        return sprintf('%s:%d.%s', $parentName, $index, $name);
    }

    /**
     * @internal
     */
    public static function buildEditableRealName(string $name, Document $document): string
    {
        $blockState = Pimcore::getContainer()->get(BlockStateStack::class)->getCurrentState();

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        if (interface_exists(TargetingDocumentInterface::class) && $document instanceof TargetingDocumentInterface && !$blockState->hasBlocks()) {
            $name = $document->getTargetGroupEditableName($name);
        }

        return $name;
    }

    public function isInDialogBox(): bool
    {
        return (bool) $this->inDialogBox;
    }

    public function getInDialogBox(): ?string
    {
        return $this->inDialogBox;
    }

    /**
     * @return $this
     */
    public function setInDialogBox(?string $inDialogBox): static
    {
        $this->inDialogBox = $inDialogBox;

        return $this;
    }

    public function getEditableDefinitionCollector(): ?EditmodeEditableDefinitionCollector
    {
        return $this->editableDefinitionCollector;
    }

    /**
     * @return $this
     */
    public function setEditableDefinitionCollector(?EditmodeEditableDefinitionCollector $editableDefinitionCollector): static
    {
        $this->editableDefinitionCollector = $editableDefinitionCollector;

        return $this;
    }

    protected function getUnserializedData(mixed $data): ?array
    {
        if (is_array($data) || is_null($data)) {
            return $data;
        }
        if (is_string($data)) {
            $unserializedData = Serialize::unserialize($data);
            if (!is_array($unserializedData) && !is_null($unserializedData)) {
                throw new InvalidArgumentException('Unserialized data must be an array or null.');
            }

            return $unserializedData;
        }

        throw new InvalidArgumentException('Data must be a string, an array or null.');
    }
}
