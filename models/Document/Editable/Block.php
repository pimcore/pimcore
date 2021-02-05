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
use Pimcore\Model;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Block extends Model\Document\Editable implements BlockInterface
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
     * @var string[]
     */
    public $suffixes = [];

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'block';
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
     */
    public function admin()
    {
        // nothing to do
        return '';
    }

    /**
     * @see EditableInterface::frontend
     */
    public function frontend()
    {
        // nothing to do
        return '';
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
        $this->indices = \Pimcore\Tool\Serialize::unserialize($data);

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
     * @return $this
     */
    public function setDefault()
    {
        if (empty($this->indices) && isset($this->config['default']) && $this->config['default']) {
            for ($i = 0; $i < intval($this->config['default']); $i++) {
                $this->indices[$i] = $i + 1;
            }
        }

        return $this;
    }

    /**
     * Loops through the block
     *
     * @return bool
     */
    public function loop()
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
     * Called before the block is rendered
     */
    public function blockConstruct()
    {
        // set the current block suffix for the child elements (0, 1, 3, ...)
        // this will be removed in blockDestruct
        $this->getBlockState()->pushIndex($this->indices[$this->current]);
    }

    /**
     * Called when the block was rendered
     */
    public function blockDestruct()
    {
        $this->getBlockState()->popIndex();
    }

    /**
     * Is called evertime a new iteration starts (new entry of the block while looping)
     *
     * @param bool $showControls
     */
    public function blockStart($showControls = true)
    {
        $attr = $this->getBlockAttributes();

        $outerAttributes = [
            'key' => $this->indices[$this->current],
        ];
        $oAttr = HtmlUtils::assembleAttributeString($outerAttributes);

        // outer element
        $this->outputEditmode('<div class="pimcore_block_entry" ' . $oAttr . ' ' . $attr . '>');

        if ($showControls) {
            $this->blockControls();
        }
    }

    /**
     * Custom position of button controls between blockStart -> blockEnd
     */
    public function blockControls()
    {
        $attr = $this->getBlockAttributes();

        $this->outputEditmode('<div class="pimcore_block_buttons" ' . $attr . '>');

        $this->outputEditmode('<div class="pimcore_block_amount" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_plus" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_minus" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_up" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_down" ' . $attr . '></div>');
        $this->outputEditmode('<div class="pimcore_block_clear" ' . $attr . '></div>');

        $this->outputEditmode('</div>'); // .pimcore_block_buttons

        $this->current++;
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
        if (empty($config['limit'])) {
            $config['limit'] = 1000000;
        }

        $this->config = $config;

        return $this;
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
        return $this->indices[$this->getCurrent()] ?? null;
    }

    /**
     * If object was serialized, set the counter back to 0
     */
    public function __wakeup()
    {
        $this->current = 0;
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
        if (($data->indices === null or is_array($data->indices)) and ($data->current == null or is_numeric($data->current))) {
            $this->indices = $data->indices;
            $this->current = $data->current;
        } else {
            throw new \Exception('cannot get  values from web service import - invalid data');
        }
    }

    /**
     * @return Block\Item[]
     */
    public function getElements()
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

    /**
     * @return string
     */
    private function getBlockAttributes(): string
    {
        $attributes = [
            'data-name' => $this->getName(),
            'data-real-name' => $this->getRealName(),
        ];

        return HtmlUtils::assembleAttributeString($attributes);
    }
}

class_alias(Block::class, 'Pimcore\Model\Document\Tag\Block');
