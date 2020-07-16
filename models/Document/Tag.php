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

use Pimcore\Document\Tag\Block\BlockName;
use Pimcore\Document\Tag\Block\BlockState;
use Pimcore\Event\DocumentEvents;
use Pimcore\Event\Model\Document\TagNameEvent;
use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Targeting\TargetingDocumentInterface;
use Pimcore\Model\Webservice;
use Pimcore\Templating\Model\ViewModel;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
 * @method void save()
 * @method void delete()
 */
abstract class Tag extends Model\AbstractModel implements Model\Document\Tag\TagInterface
{
    /**
     * Options of the current tag, can contain some configurations for the editmode, or the thumbnail name, ...
     *
     * @var array
     */
    protected $options;

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

    /**
     * @param string $type
     * @param string $name
     * @param int $documentId
     * @param array|null $config
     * @param string|null $controller
     * @param ViewModel|null $view
     * @param bool|null $editmode
     *
     * @return Tag
     */
    public static function factory($type, $name, $documentId, $config = null, $controller = null, $view = null, $editmode = null)
    {
        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.document.tag');

        /** @var Tag $tag */
        $tag = $loader->build($type);
        $tag->setName($name);
        $tag->setDocumentId($documentId);
        $tag->setController($controller);
        if (!$view) {
            // needed for the RESTImporter. For areabricks define a default implementation. Otherwise cannot find a tag handler.
            $view = new ViewModel();
        }
        $tag->setView($view);
        $tag->setEditmode($editmode);
        $tag->setOptions($config);

        return $tag;
    }

    /**
     * @return string|void
     */
    public function admin()
    {
        $options = $this->getEditmodeOptions();
        $code = $this->outputEditmodeOptions($options, true);

        $attributes = $this->getEditmodeElementAttributes($options);
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $code .= ('<div ' . $attributeString . '></div>');

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
            'options' => $this->getOptions(),
            'data' => $this->getEditmodeData(),
            'type' => $this->getType(),
            'inherited' => $this->getInherited(),
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
        ];

        $editableOptions = $this->getOptions();
        if (isset($editableOptions['class'])) {
            if (is_array($editableOptions['class'])) {
                $classes = array_merge($classes, $editableOptions['class']);
            } else {
                $classes[] = (string)$editableOptions['class'];
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
                editableConfigurations.push(' . json_encode($options, JSON_PRETTY_PRINT) . ');
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
     */
    public function getOptions()
    {
        return is_array($this->options) ? $this->options : [];
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $options;

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
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ViewModelInterface
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
        $blockedVars = ['controller', 'view', 'editmode', 'options', 'parentBlockNames', 'document'];

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
     * Receives a standard class object from webservice import and fills the current tag's data
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
     * Returns the current tag's data for web service export
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
        return \Pimcore::getContainer()->get('pimcore.document.tag.block_state_stack')->getCurrentState();
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
     */
    public static function buildTagName(string $type, string $name, Document $document = null)
    {
        // do NOT allow dots (.) and colons (:) here as they act as delimiters
        // for block hierarchy in the new naming scheme (see #1467)!
        if (!preg_match("@^[a-zA-Z0-9\-_]+$@", $name)) {
            throw new \InvalidArgumentException(
                'Only valid CSS class selectors are allowed as the name for an editable (which is basically [a-zA-Z0-9\-_]+). Your name was: ' . $name
            );
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the tag is inside a block

        $container = \Pimcore::getContainer();
        $blockState = $container->get('pimcore.document.tag.block_state_stack')->getCurrentState();
        $namingStrategy = $container->get('pimcore.document.tag.naming.strategy');

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        $targetGroupElementName = null;
        if ($document && $document instanceof TargetingDocumentInterface) {
            $targetGroupElementName = $document->getTargetGroupElementName($name);

            if (!$blockState->hasBlocks()) {
                $name = $targetGroupElementName;
            }
        }

        $tagName = $namingStrategy->buildTagName($name, $type, $blockState, $targetGroupElementName);

        $event = new TagNameEvent($type, $name, $blockState, $tagName, $document);
        \Pimcore::getEventDispatcher()->dispatch(DocumentEvents::TAG_NAME, $event);

        $tagName = $event->getTagName();

        if (strlen($tagName) > 750) {
            throw new \Exception(sprintf(
                'Composite name for editable "%s" is longer than 750 characters. Use shorter names for your editables or reduce amount of nesting levels. Name is: %s',
                $name,
                $tagName
            ));
        }

        return $tagName;
    }

    public static function buildTagRealName(string $name, Document $document): string
    {
        $container = \Pimcore::getContainer();
        $blockState = $container->get('pimcore.document.tag.block_state_stack')->getCurrentState();

        // if element not nested inside a hierarchical element (e.g. block), add the
        // targeting prefix if configured on the document. hasBlocks() determines if
        // there are any parent blocks for the current element
        if ($document instanceof TargetingDocumentInterface && !$blockState->hasBlocks()) {
            $name = $document->getTargetGroupElementName($name);
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
}
