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
    public $text;

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

        if ($data->text === null or is_string($data->text)) {
            $this->text = $data->text;
        } else {
            throw new \Exception('cannot get values from web service import - invalid data');
        }
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
     * @return string|void
     *
     * @todo: no rewriteIds method ever returns anything, why this one?
     */
    public function rewriteIds($idMapping)
    {
        $html = str_get_html($this->text);
        if (!$html) {
            return $this->text;
        }

        $s = $html->find('a[pimcore_id],img[pimcore_id]');

        if ($s) {
            foreach ($s as $el) {
                if ($el->href || $el->src) {
                    $type = $el->pimcore_type;
                    $id = (int) $el->pimcore_id;

                    if (array_key_exists($type, $idMapping)) {
                        if (array_key_exists($id, $idMapping[$type])) {
                            $el->outertext = str_replace('="' . $el->pimcore_id . '"', '="' . $idMapping[$type][$id] . '"', $el->outertext);
                        }
                    }
                }
            }
        }

        $this->text = $html->save();

        $html->clear();
        unset($html);

        return;
    }
}

class_alias(Wysiwyg::class, 'Pimcore\Model\Document\Tag\Wysiwyg');
