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
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Model\Document\Editable;

use Pimcore\Model;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Text;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Wysiwyg extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    /**
     * Contains the text
     *
     * @internal
     *
     * @var string
     */
    protected $text;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'wysiwyg';
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function getDataEditmode() /** : mixed */
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
            'document' => $document,
            'context' => $this,
        ]);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->text = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->text);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDependencies()
    {
        return Text::getDependenciesOfWysiwygText($this->text);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        return Text::getCacheTagsOfWysiwygText($this->text, $tags);
    }

    /**
     * { @inheritdoc }
     */
    public function rewriteIds($idMapping) /** : void */
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
