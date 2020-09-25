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
use Pimcore\Model;
use Pimcore\Tool\HtmlUtils;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Area extends Model\Document\Editable
{
    /**
     * @inheritDoc
     */
    public function getType()
    {
        return 'area';
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function admin()
    {
        $options = $this->getEditmodeOptions();
        $this->outputEditmodeOptions($options);

        $attributes = $this->getEditmodeElementAttributes($options);
        $attributeString = HtmlUtils::assembleAttributeString($attributes);

        $this->outputEditmode('<div ' . $attributeString . '>');

        $this->frontend();

        $this->outputEditmode('</div>');
    }

    /**
     * @inheritDoc
     */
    public function frontend()
    {
        $config = $this->getConfig();

        // TODO inject area handler via DI when tags are built through container
        $editableHandler = \Pimcore::getContainer()->get(EditableHandlerInterface::class);

        // don't show disabled bricks
        if (!$editableHandler->isBrickEnabled($this, $config['type'] && $config['dontCheckEnabled'] != true)) {
            return;
        }

        // push current block name
        $blockState = $this->getBlockState();
        $blockState->pushBlock(BlockName::createFromEditable($this));

        // create info object and assign it to the view
        $info = null;
        try {
            $info = new Area\Info();
            $info->setId($config['type']);
            $info->setEditable($this);
            $info->setIndex(0);
        } catch (\Exception $e) {
            $info = null;
        }

        // start at first index
        $blockState->pushIndex(1);

        $params = [];
        if (is_array($config['params']) && array_key_exists($config['type'], $config['params'])) {
            if (is_array($config['params'][$config['type']])) {
                $params = $config['params'][$config['type']];
            }
        }

        $info->setParams($params);

        $editableHandler->renderAreaFrontend($info);

        // remove current block and index from stack
        $blockState->popIndex();
        $blockState->popBlock();
    }

    /**
     * @inheritDoc
     */
    public function setDataFromResource($data)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setDataFromEditmode($data)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty()
    {
        return false;
    }

    /**
     * Gets an element from the referenced brick. E.g. if you have an area "myArea" which defines "gallery-single-images"
     * as used areabrick and this areabrick defines a block "gallery", you can use $area->getElement('gallery') to get
     * an instance of the block element.
     *
     * @param string $name
     *
     * @return Model\Document\Editable
     */
    public function getElement(string $name)
    {
        $document = $this->getDocument();
        $namingStrategy = \Pimcore::getContainer()->get('pimcore.document.tag.naming.strategy');

        $parentBlockNames = $this->getParentBlockNames();
        $parentBlockNames[] = $this->getName();

        $id = $namingStrategy->buildChildElementTagName($name, 'area', $parentBlockNames, 1);
        $editable = $document->getEditable($id);

        if ($editable) {
            $editable->setParentBlockNames($parentBlockNames);
        }

        return $editable;
    }
}

class_alias(Area::class, 'Pimcore\Model\Document\Tag\Area');
