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

namespace Pimcore\Model\Document;

use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\Block\BlockState;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Document\Editable\EditmodeEditableDefinitionCollector;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\EditableNameEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Tool\HtmlUtils;

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
     *
     * @var array|null
     */
    protected $config;

    /**
     * @internal
     *
     * @var string
     */
    protected $name;

    /**
     * Contains the real name of the editable without the prefixes and suffixes
     * which are generated automatically by blocks and areablocks
     *
     * @internal
     *
     * @var string
     */
    protected $realName;

    /**
     * Contains parent hierarchy names (used when building elements inside a block/areablock hierarchy)
     *
     * @var array
     */
    private $parentBlockNames = [];

    /**
     * Element belongs to the ID of the document
     *
     * @internal
     *
     * @var int
     */
    protected $documentId;

    /**
     * Element belongs to the document
     *
     * @internal
     *
     * @var Document\PageSnippet|null
     */
    protected $document;

    /**
     * In Editmode or not
     *
     * @internal
     *
     * @var bool
     */
    protected $editmode;

    /**
     * @internal
     *
     * @var bool
     */
    protected $inherited = false;

    /**
     * @internal
     *
     * @var string
     */
    protected $inDialogBox = null;

    /**
     * @var EditmodeEditableDefinitionCollector|null
     */
    private $editableDefinitionCollector;

    /**
     * @return string|void
     *
     * @throws \Exception
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

    /**
     * @param string $id
     * @param string $code
     *
     * @return string
     */
    private function wrapEditmodeContainerCodeForDialogBox(string $id, string $code): string
    {
        $code = '<template id="template__' . $id . '">' . $code . '</template>';

        return $code;
    }

    /**
     * Builds config passed to editmode frontend as JSON config
     *
     * @return array
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
     * @return mixed
     *
     * @internal
     */
    protected function getEditmodeData()
    {
        // get configuration data for admin
        if (method_exists($this, 'getDataEditmode')) {
            $data = $this->getDataEditmode();
        } else {
            $data = $this->getData();
        }

        return $data;
    }

    /**
     * Builds attributes used on the editmode HTML element
     *
     * @return array
     *
     * @internal
     */
    protected function getEditmodeElementAttributes(): array
    {
        $config = $this->getEditmodeDefinition();

        if (!isset($config['id'])) {
            throw new \RuntimeException(sprintf('Expected an "id" option to be set on the "%s" editable config array', $this->getName()));
        }

        $attributes = array_merge($this->getEditmodeBlockStateAttributes(), [
            'id' => $config['id'],
            'class' => implode(' ', $this->getEditmodeElementClasses()),
        ]);

        return $attributes;
    }

    /**
     * @return array
     *
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
     * @return array
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
     *
     * @param string $value
     */
    protected function outputEditmode($value)
    {
        if ($this->getEditmode()) {
            echo $value . "\n";
        }
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->getData();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setDocumentId($id)
    {
        $this->documentId = (int) $id;

        if ($this->document instanceof PageSnippet && $this->document->getId() !== $this->documentId) {
            $this->document = null;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

    /**
     * @param Document\PageSnippet $document
     *
     * @return $this
     */
    public function setDocument(Document\PageSnippet $document)
    {
        $this->document = $document;
        $this->documentId = (int) $document->getId();

        return $this;
    }

    /**
     * @return Document\PageSnippet
     */
    public function getDocument()
    {
        if (!$this->document) {
            $this->document = Document\PageSnippet::getById($this->documentId);
        }

        return $this->document;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return is_array($this->config) ? $this->config : [];
    }

    /**
     * {@inheritdoc}
     */
    public function setConfig($config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return self
     */
    public function addConfig(string $name, $value): self
    {
        if (!is_array($this->config)) {
            $this->config = [];
        }

        $this->config[$name] = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realName;
    }

    /**
     * @param string $realName
     */
    public function setRealName($realName)
    {
        $this->realName = $realName;
    }

    final public function setParentBlockNames($parentNames)
    {
        if (is_array($parentNames)) {
            // unfortunately we cannot make a type hint here, because of compatibility reasons
            // old versions where 'parentBlockNames' was not excluded in __sleep() have still this property
            // in the serialized data, and mostly with the value NULL, on restore this would lead to an error
            $this->parentBlockNames = $parentNames;
        }
    }

    final public function getParentBlockNames(): array
    {
        return $this->parentBlockNames;
    }

    /**
     * Returns only the properties which should be serialized
     *
     * @return array
     */
    public function __sleep()
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

    public function __clone()
    {
        parent::__clone();
        $this->document = null;
    }

    /**
     * {@inheritdoc}
     */
    final public function render()
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
     *
     * @return string
     */
    public function __toString()
    {
        $result = '';

        try {
            $result = $this->render();
        } catch (\Throwable $e) {
            if (\Pimcore::inDebugMode()) {
                // the __toString method isn't allowed to throw exceptions
                $result = '<b style="color:#f00">' . $e->getMessage().'</b><br/>'.$e->getTraceAsString();

                return $result;
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

    /**
     * @return bool
     */
    public function getEditmode()
    {
        return $this->editmode;
    }

    /**
     * @param bool $editmode
     *
     * @return $this
     */
    public function setEditmode($editmode)
    {
        $this->editmode = (bool) $editmode;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDataForResource()
    {
        $this->checkValidity();

        return $this->getData();
    }

    /**
     * @param Model\Document\PageSnippet $ownerDocument
     * @param array $tags
     *
     * @return array
     */
    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        return $tags;
    }

    /**
     * This is a dummy and is mostly implemented by relation types
     */
    public function resolveDependencies()
    {
        return [];
    }

    /**
     * @return bool
     */
    public function checkValidity()
    {
        return true;
    }

    /**
     * @param bool $inherited
     *
     * @return $this
     */
    public function setInherited($inherited)
    {
        $this->inherited = $inherited;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInherited()
    {
        return $this->inherited;
    }

    /**
     * @internal
     *
     * @return BlockState
     */
    protected function getBlockState(): BlockState
    {
        return $this->getBlockStateStack()->getCurrentState();
    }

    /**
     * @internal
     *
     * @return BlockStateStack
     */
    protected function getBlockStateStack(): BlockStateStack
    {
        return \Pimcore::getContainer()->get(BlockStateStack::class);
    }

    /**
     * Builds an editable name for an editable, taking current
     * block state (block, index) and targeting into account.
     *
     * @internal
     *
     * @param string $type
     * @param string $name
     * @param Document|null $document
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function buildEditableName(string $type, string $name, Document $document = null)
    {
        // do NOT allow dots (.) and colons (:) here as they act as delimiters
        // for block hierarchy in the new naming scheme (see #1467)!
        if (!preg_match("@^[a-zA-Z0-9\-_]+$@", $name)) {
            throw new \InvalidArgumentException(
                'Only valid CSS class selectors are allowed as the name for an editable (which is basically [a-zA-Z0-9\-_]+). Your name was: ' . $name
            );
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the editable is inside a block

        $container = \Pimcore::getContainer();
        $blockState = $container->get(BlockStateStack::class)->getCurrentState();

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        $targetGroupEditableName = null;
        if ($document && $document instanceof TargetingDocumentInterface) {
            $targetGroupEditableName = $document->getTargetGroupEditableName($name);

            if (!$blockState->hasBlocks()) {
                $name = $targetGroupEditableName;
            }
        }

        $editableName = self::doBuildName($name, $type, $blockState, $targetGroupEditableName);

        $event = new EditableNameEvent($type, $name, $blockState, $editableName, $document);
        \Pimcore::getEventDispatcher()->dispatch($event, DocumentEvents::EDITABLE_NAME);

        $editableName = $event->getEditableName();

        if (strlen($editableName) > 750) {
            throw new \Exception(sprintf(
                'Composite name for editable "%s" is longer than 750 characters. Use shorter names for your editables or reduce amount of nesting levels. Name is: %s',
                $name,
                $editableName
            ));
        }

        return $editableName;
    }

    /**
     * @param string $name
     * @param string $type
     * @param BlockState $blockState
     * @param string|null $targetGroupElementName
     *
     * @return string
     */
    private static function doBuildName(string $name, string $type, BlockState $blockState, string $targetGroupElementName = null): string
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

            $tmpName = $name;
            if (is_array($tmpBlocks)) {
                $tmpName = self::buildHierarchicalName($name, $tmpBlocks, $tmpIndexes);
            }

            $previousBlockName = $blocks[count($blocks) - 1]->getName();
            if ($previousBlockName === $tmpName || ($targetGroupElementName && $previousBlockName === $targetGroupElementName)) {
                array_pop($blocks);
                array_pop($indexes);
            }
        }

        return self::buildHierarchicalName($name, $blocks, $indexes);
    }

    /**
     * @param string $name
     * @param BlockName[] $blocks
     * @param int[] $indexes
     *
     * @return string
     */
    private static function buildHierarchicalName(string $name, array $blocks, array $indexes): string
    {
        if (count($indexes) > count($blocks)) {
            throw new \RuntimeException(sprintf('Index count %d is greater than blocks count %d', count($indexes), count($blocks)));
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
     * @param string $name
     * @param string $type
     * @param array $parentBlockNames
     * @param int $index
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function buildChildEditableName(string $name, string $type, array $parentBlockNames, int $index): string
    {
        if (count($parentBlockNames) === 0) {
            throw new \Exception(sprintf(
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
     *
     * @param string $name
     * @param Document $document
     *
     * @return string
     */
    public static function buildEditableRealName(string $name, Document $document): string
    {
        $blockState = \Pimcore::getContainer()->get(BlockStateStack::class)->getCurrentState();

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        if ($document instanceof TargetingDocumentInterface && !$blockState->hasBlocks()) {
            $name = $document->getTargetGroupEditableName($name);
        }

        return $name;
    }

    /**
     * @return bool
     */
    public function isInDialogBox(): bool
    {
        return (bool) $this->inDialogBox;
    }

    /**
     * @return string|null
     */
    public function getInDialogBox(): ?string
    {
        return $this->inDialogBox;
    }

    /**
     * @param string|null $inDialogBox
     *
     * @return $this
     */
    public function setInDialogBox(?string $inDialogBox): self
    {
        $this->inDialogBox = $inDialogBox;

        return $this;
    }

    /**
     * @return EditmodeEditableDefinitionCollector|null
     */
    public function getEditableDefinitionCollector(): ?EditmodeEditableDefinitionCollector
    {
        return $this->editableDefinitionCollector;
    }

    /**
     * @param EditmodeEditableDefinitionCollector|null $editableDefinitionCollector
     *
     * @return $this
     */
    public function setEditableDefinitionCollector(?EditmodeEditableDefinitionCollector $editableDefinitionCollector): self
    {
        $this->editableDefinitionCollector = $editableDefinitionCollector;

        return $this;
    }
}
