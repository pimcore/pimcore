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

namespace Pimcore\Model\Document\Editable;

use DOMElement;
use Pimcore;
use Pimcore\Model;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Text;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Wysiwyg extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    private static HtmlSanitizer $pimcoreWysiwygSanitizer;

    /**
     * Contains the text
     *
     * @internal
     */
    protected ?string $text = null;

    private static function getWysiwygSanitizer(): HtmlSanitizer
    {
        return self::$pimcoreWysiwygSanitizer ??= Pimcore::getContainer()->get(Text::PIMCORE_WYSIWYG_SANITIZER_ID);
    }

    public function getType(): string
    {
        return 'wysiwyg';
    }

    public function getData(): mixed
    {
        return (string) $this->text;
    }

    public function getText(): string
    {
        return $this->getData();
    }

    public function getDataEditmode(): ?string
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
            'document' => $document,
            'context' => $this,
        ]);
    }

    public function frontend()
    {
        $document = $this->getDocument();

        return Text::wysiwygText($this->text, [
                'document' => $document,
                'context' => $this,
            ]);
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->text);
    }

    public function resolveDependencies(): array
    {
        return Text::getDependenciesOfWysiwygText($this->text);
    }

    public function getCacheTags(Model\Document\PageSnippet $ownerDocument, array $tags = []): array
    {
        return Text::getCacheTagsOfWysiwygText($this->text, $tags);
    }

    public function rewriteIds(array $idMapping): void
    {
        $html = new DomCrawler($this->text);

        $elements = $html->filter('a[pimcore_id], img[pimcore_id]');

        /** @var DOMElement $el */
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

    public function save(): void
    {
        if (is_string($this->text)) {
            $helper = self::getWysiwygSanitizer();
            $this->text = $helper->sanitizeFor('body', $this->text);
        }
        $this->getDao()->save();
    }
}
