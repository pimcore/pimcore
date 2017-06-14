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

use Pimcore\Logger;
use Pimcore\Model;
use Pimcore\Model\Document;
use Pimcore\Model\Webservice;
use Pimcore\Templating\Model\ViewModelInterface;
use Pimcore\Tool\HtmlUtils;
use Pimcore\View;

/**
 * @method \Pimcore\Model\Document\Tag\Dao getDao()
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
     * @var \Pimcore\Controller\Action
     */
    protected $controller;

    /**
     * @var ViewModelInterface|View
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
     * @param $type
     * @param $name
     * @param $documentId
     * @param null $config
     * @param null $controller
     * @param null $view
     * @param null $editmode
     *
     * @return mixed
     */
    public static function factory($type, $name, $documentId, $config = null, $controller = null, $view = null, $editmode = null)
    {
        $loader = \Pimcore::getContainer()->get('pimcore.implementation_loader.document.tag');

        /** @var Tag $tag */
        $tag = $loader->build($type);
        $tag->setName($name);
        $tag->setDocumentId($documentId);
        $tag->setController($controller);
        $tag->setView($view);
        $tag->setEditmode($editmode);
        $tag->setOptions($config);

        return $tag;
    }

    /**
     * @return string
     */
    public function admin()
    {
        $options = $this->getEditmodeOptions();
        $code = $this->outputEditmodeOptions($options, true);

        $attributes      = $this->getEditmodeElementAttributes($options);
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
            'id'        => 'pimcore_editable_' . $this->getName(),
            'name'      => $this->getName(),
            'realName'  => $this->getRealName(),
            'options'   => $this->getOptions(),
            'data'      => $this->getEditmodeData(),
            'type'      => $this->getType(),
            'inherited' => $this->getInherited()
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

        $attributes = [
            'id'             => $options['id'],
            'class'          => implode(' ', $this->getEditmodeElementClasses()),
            'data-name'      => $this->getName(),
            'data-real-name' => $this->getRealName(),
            'data-type'      => $this->getType()
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
            'pimcore_tag_' . $this->getType()
        ];

        $editableOptions = $this->getOptions();
        if (isset($editableOptions['class'])) {
            if (is_array($editableOptions['class'])) {
                $classes = array_merge($classes, $editableOptions['class']);
            } else {
                $classes[] = (string)$editableOptions['class'];
            }

            $classes[] = (string)$editableOptions['class'];
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
     * @return string
     */
    protected function outputEditmodeOptions(array $options, $return = false)
    {
        $code = '
            <script type="text/javascript">
                editableConfigurations.push(' . json_encode($options) . ');
            </script>
        ';

        if ($return) {
            return $code;
        }

        $this->outputEditmode($code);
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
     * @param \Pimcore\Controller\Action $controller
     *
     * @return $this
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @return \Pimcore\Controller\Action
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * @param ViewModelInterface|View $view
     *
     * @return $this
     */
    public function setView($view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return ViewModelInterface|View
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

    final public function setParentBlockNames(array $parentNames)
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
     * @return array
     */
    public function __sleep()
    {

        // here the "normal" task of __sleep ;-)
        $blockedVars = ['dao', 'controller', 'view', 'editmode', 'options'];
        $vars = get_object_vars($this);
        foreach ($vars as $key => $value) {
            if (!in_array($key, $blockedVars)) {
                $finalVars[] = $key;
            }
        }

        return $finalVars;
    }

    /**
     * direct output to the frontend
     *
     * @return string
     */
    public function __toString()
    {
        $return = '';

        try {
            if ($this->editmode) {
                $return = $this->admin();
            } else {
                $return = $this->frontend();
            }
        } catch (\Exception $e) {
            if (\Pimcore::inDebugMode()) {
                // the __toString method isn't allowed to throw exceptions
                $return = '<b style="color:#f00">' . $e->getMessage().'</b><br/>'.$e->getTraceAsString();

                return $return;
            }

            Logger::error('toString() returned an exception: {exception}', [
                'exception' => $e
            ]);

            return '';
        }

        if (is_string($return) || is_numeric($return)) {
            // we have to cast to string, because int/float is not auto-converted and throws an exception
            return (string) $return;
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
     * @return $this
     */
    public function getDataForResource()
    {
        $this->checkValidity();

        return $this->getData();
    }

    /**
     * @param $ownerDocument
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
     * @param Webservice\Data\Document\Element $wsElement
     * @param $document
     * @param array $params,
     * @param $idMapper
     * @param mixed $params
     *
     * @return Webservice\Data\Document\Element
     */
    public function getFromWebserviceImport($wsElement, $document = null, $params = [], $idMapper = null)
    {
        return $wsElement;
    }

    /**
     * Returns the current tag's data for web service export
     *
     * @param $document
     * @param mixed $params
     * @abstract
     *
     * @return array
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
     * @param $inherited
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
     * Builds a tag name for an editable, taking current
     * block state (block, index) into account.
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

        // check for persona content
        if ($document && $document instanceof Document\Page && $document->getUsePersona()) {
            $name = $document->getPersonaElementName($name);
        }

        // @todo add document-id to registry key | for example for embeded snippets
        // set suffixes if the tag is inside a block

        $container      = \Pimcore::getContainer();
        $blockState     = $container->get('pimcore.document.tag.block_state_stack')->getCurrentState();
        $namingStrategy = $container->get('pimcore.document.tag.naming.strategy');

        $tagName = $namingStrategy->buildTagName($name, $type, $blockState);

        if (strlen($tagName) > 750) {
            throw new \Exception(sprintf(
                'Composite name for editable "%s" is longer than 750 characters. Use shorter names for your editables or reduce amount of nesting levels. Name is: %s',
                $name, $tagName
            ));
        }

        return $tagName;
    }
}
