<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Model\Document\Editable;

use DOMElement;
use Pimcore;
use Pimcore\Model;
use Pimcore\Tool\DomCrawler;
use Pimcore\Tool\Text;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Wysiwyg extends Model\Document\Editable implements IdRewriterInterface, EditmodeDataInterface
{
    private static HtmlSanitizerInterface $pimcoreWysiwygSanitizer;

    /**
     * Contains the text
     *
     * @internal
     */
    protected ?string $text = null;

    private static function getWysiwygSanitizer(): HtmlSanitizerInterface
    {
        return self::$pimcoreWysiwygSanitizer ??= Pimcore::getContainer()->get(Text::PIMCORE_WYSIWYG_SANITIZER_ID);
    }

    /**
     * Sanitizing must not depend on the value having gone through a regular save: a draft version
     * (saved via "version"/"autoSave" and never passed to {@see save()}) can still hold HTML a
     * regular save would have stripped. Rendering sanitizes the value itself instead of relying on
     * the persistence path it took.
     *
     * A value stored by a regular save is therefore sanitized again when it is rendered, so the
     * sanitizer has to return its own output unchanged. The shipped `pimcore.wysiwyg_sanitizer`
     * configuration does that for the markup the editors produce, with three exceptions that do not
     * change what a browser displays: text inside `<iframe>` is escaped once more, an attribute value
     * containing a backtick gets one more trailing space, and a value consisting only of whitespace
     * becomes an empty string. A project that replaces or decorates the sanitizer service has to keep
     * its output stable in the same way.
     */
    private static function sanitize(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        return self::getWysiwygSanitizer()->sanitizeFor('body', $text);
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

        return Text::wysiwygTextKeepingLinks(self::sanitize($this->text), [
            'document' => $document,
            'context' => $this,
        ]);
    }

    public function frontend()
    {
        $document = $this->getDocument();

        return Text::wysiwygText(self::sanitize($this->text), [
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
        $changed = false;

        /** @var DOMElement $el */
        foreach ($elements as $el) {
            if ($el->hasAttribute('href') || $el->hasAttribute('src')) {
                $type = $el->getAttribute('pimcore_type');
                $id = (int)$el->getAttribute('pimcore_id');

                if ($idMapping[$type][$id] ?? false) {
                    $el->setAttribute('pimcore_id', strtr($el->getAttribute('pimcore_id'), $idMapping[$type]));
                    $changed = true;
                }
            }
        }

        // only write back if an id was mapped: the DOM round trip re-encodes the text (e.g. &nbsp;),
        // which would make the editable differ from its content main document and break the inheritance
        if ($changed) {
            $this->text = $html->html();
        }

        $html->clear();
        unset($html);
    }

    public function save(): void
    {
        $this->text = self::sanitize($this->text);
        $this->getDao()->save();
    }
}
