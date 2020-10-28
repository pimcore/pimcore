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

namespace Pimcore\Model\Document;

use Pimcore\Document\Editable\Block\BlockName;
use Pimcore\Document\Editable\Block\BlockState;
use Pimcore\Document\Editable\Block\BlockStateStack;
use Pimcore\Document\Tag\NamingStrategy\NamingStrategyInterface;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\EditableNameEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Webservice;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 * @method void save()
 * @method void delete()
 */
abstract class Editable extends Model\AbstractModel implements Model\Document\Editable\EditableInterface
{
    /**
     * Options of the current tag, can contain some configurations for the editmode, or the thumbnail name, ...
     *
     * @var array
     *
     * @deprecated will be removed in Pimcore 7. use $config instead
     */
    protected $options;

    /**
     * Contains some configurations for the editmode, or the thumbnail name, ...
     *
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $name;

    /**
     * Contains the real name of the editable without the prefixes and suffixes
     * which are generated automatically by blocks and areablocks
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
     * @var int
     */
    protected $documentId;

    /**
     * Element belongs to the document
     *
     * @var Document\PageSnippet|null
     */
    protected $document;

    /**
     * @deprecated Unused - will be removed in 7.0
     *
     * @var string|null
     */
    protected $controller;

    /**
     * @var ViewModelInterface|null
     *
     * @deprecated
     */
    protected $view;

    /**
     * In Editmode or not
     *
     * @var bool
     */
    protected $editmode;

    /**
     * @var bool
     */
    protected $inherited = false;

    public function __construct()
    {
        $this->options = & $this->config;
    }

    /**
     * @var string
     */
    protected $inDialogBox = null;

    /**
     * @param string $type
     * @param string $name
     * @param int $documentId
     * @param array|null $config
     * @param string|null $controller
     * @param ViewModel|null $view
     * @param bool|null $editmode
     *
     * @return Editable
     */
    public static function factory($type, $name, $documentId, $config = null, $controller = null, $view = null, $editmode = null)
    {
        $loader = \Pimcore::getContainer()->get(Document\Editable\Loader\EditableLoader::class);

        /** @var Editable $editable */
        $editable = $loader->build($type);
        $editable->setName($name);
        $editable->setDocumentId($documentId);
        $editable->setController($controller);
        if (!$view) {
            // needed for the RESTImporter. For areabricks define a default implementation. Otherwise cannot find a tag handler.
            $view = new ViewModel();
        }
        $editable->setView($view);
        $editable->setEditmode($editmode);
        $editable->setConfig($config);

        return $editable;
    }

    /**
     * @return string|void
     *
     * @throws \Exception
     */
    public function admin()
    {
        $options = $this->getEditmodeOptions();
        $code = $this->outputEditmodeOptions($options, true);

        $attributes = $this->getEditmodeElementAttributes($options);
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $htmlContainerCode = ('<div ' . $attributeString . '></div>');

        if ($this->isInDialogBox()) {
            $htmlContainerCode = $this->wrapEditmodeContainerCodeForDialogBox($attributes['id'], $htmlContainerCode);
        }

        $code .= $htmlContainerCode;

        return $code;
    }

    /**
     * @param string $id
     * @param string $code
     *
     * @return string
     */
    protected function wrapEditmodeContainerCodeForDialogBox(string $id, string $code): string
    {
        $code = '<template id="template__' . $id . '">' . $code . '</template>';

        return $code;
    }

    /**
     * Builds options passed to editmode frontend as JSON config
     *
     * @return array
     */
    protected function getEditmodeOptions(): array
    {
        $options = [
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

        return $options;
    }

    /**
     * Builds data used for editmode
     *
     * @return mixed
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
     * @param array $options
     *
     * @return array
     */
    protected function getEditmodeElementAttributes(array $options): array
    {
        if (!isset($options['id'])) {
            throw new \RuntimeException(sprintf('Expected an "id" option to be set on the "%s" editable options array', $this->getName()));
        }

        $attributes = array_merge($this->getEditmodeBlockStateAttributes(), [
            'id' => $options['id'],
            'class' => implode(' ', $this->getEditmodeElementClasses()),
        ]);

        return $attributes;
    }

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
     */
    protected function getEditmodeElementClasses(): array
    {
        $classes = [
            'pimcore_editable',
            'pimcore_tag_' . $this->getType(),
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
     * Push editmode options into the JS config array
     *
     * @param array $options
     * @param bool $return
     *
     * @return string|void
     */
    protected function outputEditmodeOptions(array $options, $return = false)
    {
        // filter all non-scalar values before we pass them to the config object (JSON)
        $clean = function ($value) use (&$clean) {
            if (is_array($value)) {
                foreach ($value as &$item) {
                    $item = $clean($item);
                }
            } elseif (!is_scalar($value)) {
                $value = null;
            }

            return $value;
        };
        $options = $clean($options);

        $code = '
            <script>
                editableDefinitions.push(' . json_encode($options, JSON_PRETTY_PRINT) . ');
            </script>
        ';

        if (json_last_error()) {
            throw new \Exception('json encode failed: ' . json_last_error_msg());
        }

        if ($return) {
            return $code;
        }

        $this->outputEditmode($code);

        return;
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
     * @return array
     *
     * @deprecated will be removed in Pimcore 7. Use getConfig() instead.
     */
    public function getOptions()
    {
        return $this->getConfig();
    }

    /**
     * @param array $options
     *
     * @return $this
     *
     * @deprecated will be removed in Pimcore 7. Use setConfig() instead.
     */
    public function setOptions($options)
    {
        return $this->setConfig($options);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return is_array($this->config) ? $this->config : [];
    }

    /**
     * @param array $config
     *
     * @return $this
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
    public function setOption(string $name, $value): self
    {
        if (!is_array($this->options)) {
            $this->options = [];
        }

        $this->options[$name] = $value;

        return $this;
    }

    /**
     * @deprecated
     *
     * @param string|null $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @deprecated
     *
     * @return string|null
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param ViewModelInterface $view
     *
     * @return $this
     *
     * @deprecated
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ViewModelInterface
     *
     * @deprecated
     */
    public function getView()
    {
        return $this->view;
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
        $blockedVars = ['controller', 'view', 'editmode', 'options', 'config', 'parentBlockNames', 'document'];

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
        $this->view = null;
        $this->document = null;
    }

    /**
     * direct output to the frontend
     *
     * @return string
     */
    public function render()
    {
        if ($this->editmode) {
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
    public function getCacheTags($ownerDocument, $tags = [])
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
     * Receives a standard class object from webservice import and fills the current editable's data
     *
     * @abstract
     *
     * @deprecated
     *
     * @param Webservice\Data\Document\Element $wsElement
     * @param Model\Document\PageSnippet $document
     * @param array $params
     * @param Model\Webservice\IdMapperInterface|null $idMapper
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
    }

    /**
     * Returns the current editable's data for web service export
     *
     * @deprecated
     *
     * @param Model\Document\PageSnippet|null $document
     * @param array $params
     * @abstract
     *
     * @return mixed
     */
    public function getForWebserviceExport($document = null, $params = [])
    {
        $keys = get_object_vars($this);

        $el = [];
        foreach ($keys as $key => $value) {
            if ($value instanceof Model\Element\ElementInterface) {
                $value = $value->getId();
            }
            $className = Webservice\Data\Mapper::findWebserviceClass($value, 'out');
            $el[$key] = Webservice\Data\Mapper::map($value, $className, 'out');
        }

        unset($el['dao']);
        unset($el['documentId']);
        unset($el['document']);
        unset($el['controller']);
        unset($el['view']);
        unset($el['editmode']);

        $el = Webservice\Data\Mapper::toObject($el);

        return $el;
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
     * TODO inject block state via DI
     *
     * @return BlockState
     */
    protected function getBlockState(): BlockState
    {
        return \Pimcore::getContainer()->get(BlockStateStack::class)->getCurrentState();
    }

    /**
     * Builds a tag name for an editable, taking current
     * block state (block, index) and targeting into account.
     *
     * @param string $type
     * @param string $name
     * @param Document|null $document
     *
     * @return string
     *
     * @throws \Exception
     *
     * @deprecated since v6.8 and will be removed in 7. use buildEditableName() instead
     */
    public static function buildTagName(string $type, string $name, Document $document = null)
    {
        return self::buildEditableName($type, $name, $document);
    }

    /**
     * Builds an editable name for an editable, taking current
     * block state (block, index) and targeting into account.
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
        /**
         * @var NamingStrategyInterface
         */
        $namingStrategy = $container->get('pimcore.document.tag.naming.strategy');

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

        $editableName = $namingStrategy->buildTagName($name, $type, $blockState, $targetGroupEditableName);

        $event = new EditableNameEvent($type, $name, $blockState, $editableName, $document);
        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::EDITABLE_NAME, $event);

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
     * @param Document $document
     *
     * @return string
     *
     * @deprecated since v6.8 and will be removed in 7. Use buildEditableRealName() instead
     */
    public static function buildTagRealName(string $name, Document $document): string
    {
        return self::buildEditableRealName($name, $document);
    }

    /**
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
     * @deprecated
     *
     * @param array|object $data
     *
     * @return object
     */
    public function sanitizeWebserviceData($data)
    {
        if (is_array($data)) {
            $data = (object) $data;
        }

        return $data;
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
}

class_alias(Editable::class, 'Pimcore\Model\Document\Tag');
