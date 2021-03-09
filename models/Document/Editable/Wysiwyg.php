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

use Pimcore\Model;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Text;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Wysiwyg extends Model\Document\Editable
{
    /**
     * Contains the text
     *
     * @var string
     */
    protected $text;

    /**
     * @see EditableInterface::getType
     *
     * @return string
     */
    public function getType()
    {
        return 'wysiwyg';
    }

    /**
     * @see EditableInterface::getData
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->text;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->getData();
    }

    /**
     * Converts the data so it's suitable for the editmode
     *
     * @return mixed
     */
    public function getDataEditmode()
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
            'document' => $document,
            'context' => $this,
        ]);
    }

    /**
     * @see EditableInterface::frontend
     *
     * @return string
     */
    public function frontend()
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
                'document' => $document,
                'context' => $this,
            ]);
    }

    /**
     * @see EditableInterface::setDataFromResource
     *
     * @param string $data
     *
     * @return $this
     */
    public function setDataFromResource($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * @see EditableInterface::setDataFromEditmode
     *
     * @param string $data
     *
     * @return $this
     */
    public function setDataFromEditmode($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->text);
    }

    /**
     * @return array
     */
    public function resolveDependencies()
    {
        return Text::getDependenciesOfWysiwygText($this->text);
    }

    /**
     * @param Model\Document\PageSnippet $ownerDocument
     * @param array $blockedTags
     *
     * @return array
     */
    public function getCacheTags($ownerDocument, $blockedTags = [])
    {
        return Text::getCacheTagsOfWysiwygText($this->text, $blockedTags);
    }

    /**
     * Rewrites id from source to target, $idMapping contains
     * array(
     *  "document" => array(
     *      SOURCE_ID => TARGET_ID,
     *      SOURCE_ID => TARGET_ID
     *  ),
     *  "object" => array(...),
     *  "asset" => array(...)
     * )
     *
     * @param array $idMapping
     *
     * @return void
     *
     */
    public function rewriteIds($idMapping)
    {
        $html = new DomCrawler($this->text);

        $elements = $html->filter('a[pimcore_id], img[pimcore_id]');

        /** @var \DOMElement $el */
        foreach ($elements as $el) {
            if ($el->hasAttribute('href') || $el->hasAttribute('src')) {
                $type = $el->getAttribute('pimcore_type');
                $id = (int)$el->getAttribute('pimcore_id');

                if ($idMapping[$type][$id] ?? false) {
                    $el->setAttribute('pimcore_id', strtr($el->getAttribute('pimcore_id'), $idMapping[$type]));
                }
            }
        }

        $this->text = $html->html();

        $html->clear();
        unset($html);
    }
}
