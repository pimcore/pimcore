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

namespace Pimcore\Model\DataObject\Data;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\OwnerAwareFieldInterface;
use Pimcore\Model\DataObject\Traits\ObjectVarTrait;
use Pimcore\Model\DataObject\Traits\OwnerAwareFieldTrait;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable\Link\AttributeSanitizer;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;

class Link implements OwnerAwareFieldInterface
{
    use OwnerAwareFieldTrait;
    use ObjectVarTrait;

    protected string $text = '';

    protected ?string $internalType = null;

    protected ?int $internal = null;

    protected ?string $direct = null;

    protected ?string $linktype = null;

    protected ?string $target = null;

    protected string $parameters = '';

    protected string $anchor = '';

    protected string $title = '';

    protected string $accesskey = '';

    protected string $rel = '';

    protected string $tabindex = '';

    protected string $class = '';

    protected string $attributes = '';

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return $this
     */
    public function setText(string $text): static
    {
        $this->text = $text;
        $this->markMeDirty();

        return $this;
    }

    public function getInternalType(): ?string
    {
        return $this->internalType;
    }

    /**
     * @return $this
     */
    public function setInternalType(?string $internalType): static
    {
        $this->internalType = $internalType;
        $this->markMeDirty();

        return $this;
    }

    public function getInternal(): ?int
    {
        return $this->internal;
    }

    /**
     * @return $this
     */
    public function setInternal(?int $internal): static
    {
        $this->internal = $internal;
        $this->markMeDirty();

        return $this;
    }

    public function getDirect(): ?string
    {
        return $this->direct;
    }

    /**
     * @return $this
     */
    public function setDirect(?string $direct): static
    {
        $this->direct = $direct;
        $this->markMeDirty();

        return $this;
    }

    public function getLinktype(): ?string
    {
        return $this->linktype;
    }

    /**
     * @return $this
     */
    public function setLinktype(?string $linktype): static
    {
        $this->linktype = $linktype;
        $this->markMeDirty();

        return $this;
    }

    public function getTarget(): ?string
    {
        return $this->target;
    }

    /**
     * @return $this
     */
    public function setTarget(?string $target): static
    {
        $this->target = $target;
        $this->markMeDirty();

        return $this;
    }

    public function getParameters(): string
    {
        return $this->parameters;
    }

    /**
     * @return $this
     */
    public function setParameters(string $parameters): static
    {
        $this->parameters = $parameters;
        $this->markMeDirty();

        return $this;
    }

    public function getAnchor(): string
    {
        return $this->anchor;
    }

    /**
     * @return $this
     */
    public function setAnchor(string $anchor): static
    {
        $this->anchor = $anchor;
        $this->markMeDirty();

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return $this
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        $this->markMeDirty();

        return $this;
    }

    public function getAccesskey(): string
    {
        return $this->accesskey;
    }

    /**
     * @return $this
     */
    public function setAccesskey(string $accesskey): static
    {
        $this->accesskey = $accesskey;
        $this->markMeDirty();

        return $this;
    }

    public function getRel(): string
    {
        return $this->rel;
    }

    /**
     * @return $this
     */
    public function setRel(string $rel): static
    {
        $this->rel = $rel;
        $this->markMeDirty();

        return $this;
    }

    public function getTabindex(): string
    {
        return $this->tabindex;
    }

    /**
     * @return $this
     */
    public function setTabindex(string $tabindex): static
    {
        $this->tabindex = $tabindex;
        $this->markMeDirty();

        return $this;
    }

    /**
     * @return $this
     */
    public function setAttributes(string $attributes): static
    {
        $this->attributes = $attributes;
        $this->markMeDirty();

        return $this;
    }

    public function getAttributes(): string
    {
        return $this->attributes;
    }

    /**
     * @return $this
     */
    public function setClass(string $class): static
    {
        $this->class = $class;
        $this->markMeDirty();

        return $this;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @return $this
     */
    public function setPath(string $path): static
    {
        if (!empty($path)) {
            $matchedElement = null;
            if ($this->getLinktype() == 'internal' && $this->getInternalType()) {
                $matchedElement = Service::getElementByPath($this->getInternalType(), $path);
                if ($matchedElement) {
                    $this->linktype = 'internal';
                    $this->internalType = $this->getInternalType();
                    $this->internal = $matchedElement->getId();
                }
            }

            if (!$matchedElement) {
                if ($document = Document::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'document';
                    $this->internal = $document->getId();
                } elseif ($asset = Asset::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'asset';
                    $this->internal = $asset->getId();
                } elseif ($object = Concrete::getByPath($path)) {
                    $this->linktype = 'internal';
                    $this->internalType = 'object';
                    $this->internal = $object->getId();
                } else {
                    $this->linktype = 'direct';
                    $this->internalType = null;
                    $this->direct = $path;
                }
            }
        }
        $this->markMeDirty();

        return $this;
    }

    public function getPath(): string
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getElement() instanceof ElementInterface) {
                $path = $this->getElement()->getFullPath();
            }
        } else {
            $path = $this->getDirect() ?? '';
        }

        return $path;
    }

    /**
     * Returns the plain text path of the link
     *
     */
    public function getHref(): string
    {
        $path = '';
        if ($this->getLinktype() == 'internal') {
            if ($this->getElement() instanceof Document || $this->getElement() instanceof Asset) {
                $path = $this->getElement()->getFullPath();
            } elseif ($this->getElement() instanceof Concrete) {
                if ($linkGenerator = $this->getElement()->getClass()->getLinkGenerator()) {
                    $path = $linkGenerator->generate($this->getElement(), [
                        'context' => $this,
                    ]);
                }
            }
        } else {
            $path = $this->getDirect() ?? '';
        }

        if (strlen($this->getParameters()) > 0) {
            $path .= '?' . str_replace('?', '', $this->getParameters());
        }
        if (strlen($this->getAnchor()) > 0) {
            $path .= '#' . str_replace('#', '', $this->getAnchor());
        }

        return $path;
    }

    public function getElement(): DataObject|Asset|Document|null
    {
        $element = null;

        if ($this->internal !== null) {
            if ($this->internalType === 'document') {
                $element = Document::getById($this->internal);
            } elseif ($this->internalType === 'asset') {
                $element = Asset::getById($this->internal);
            } elseif ($this->internalType === 'object') {
                $element = Concrete::getById($this->internal);
            }
        }

        return $element;
    }

    /**
     * @return $this
     */
    public function setElement(ElementInterface $object): static
    {
        $this->internal = $object->getId();
        $this->internalType = Service::getElementType($object);
        $this->markMeDirty();

        return $this;
    }

    /**
     * Renders the link as an anchor tag. Whether script-executing URL schemes and event-handler
     * attributes are rejected depends on the active
     * \Pimcore\Model\Document\Editable\Link\AttributeSanitizer policy (shared with the Document
     * Link editable, see GHSA-h78x-47qg-qjmq): the permissive default keeps the historical output,
     * strict() (config "pimcore.documents.editables.link_sanitizer.strict") rejects them.
     */
    public function getHtml(): string
    {
        $attributes = ['rel', 'tabindex', 'accesskey', 'title', 'target', 'class'];
        $attribs = [];
        foreach ($attributes as $a) {
            if ($this->$a) {
                $attribs[] = $a . '="' . self::escapeDoubleQuotes((string) $this->$a) . '"';
            }
        }

        $freeFormAttributes = $this->getRenderedFreeFormAttributes();
        if ($freeFormAttributes !== '') {
            $attribs[] = $freeFormAttributes;
        }

        $href = $this->getHref();
        $text = $this->getText();

        // Fall back to the URL as visible text when no text is set.
        // Use a strict check so a legitimate "0" text is not treated as empty.
        if ($text === '') {
            $text = $href;

            if ($text === '') {
                return '';
            }
        }

        return '<a href="' . self::escapeDoubleQuotes($this->getRenderedHref($href)) . '" ' . implode(' ', $attribs) . '>' . htmlspecialchars($text) . '</a>';
    }

    /**
     * Escapes only the character that can end a double-quoted attribute value, so the value cannot
     * break out of its attribute while every input that was safe before renders byte-identically.
     */
    private static function escapeDoubleQuotes(string $value): string
    {
        return str_replace('"', '&quot;', $value);
    }

    private function getRenderedHref(string $href): string
    {
        if (!AttributeSanitizer::getInstance()->isUrlAllowed($href)) {
            return '';
        }

        // only the unconfigured permissive default is deprecated - an application that installed
        // its own policy via AttributeSanitizer::setInstance() has opted out on purpose
        if ($href !== '' && !AttributeSanitizer::isConfigured() && !AttributeSanitizer::strict()->isUrlAllowed($href)) {
            trigger_deprecation(
                'pimcore/pimcore',
                '2026.3',
                'Rendering a DataObject Link href with a URL scheme that the stricter policy closing'
                . ' GHSA-h78x-47qg-qjmq would reject. The permissive Link sanitizer default is deprecated and'
                . ' will be removed in 2027.1; set "pimcore.documents.editables.link_sanitizer.strict: true" to'
                . ' reject it now.'
            );
        }

        return $href;
    }

    /**
     * The permissive default emits the free-form `attributes` string exactly as stored. Under a
     * policy that rejects editor-supplied attribute keys it is instead parsed into name/value
     * pairs and re-serialized, so it can neither break out of the opening tag nor carry event
     * handlers; anything that does not parse as an attribute, or whose key is rejected, is dropped.
     */
    private function getRenderedFreeFormAttributes(): string
    {
        $raw = $this->getAttributes();
        if (!$raw) {
            return '';
        }

        $sanitizer = AttributeSanitizer::getInstance();
        if ($sanitizer->rejectsEditorSuppliedAttributeKeys()) {
            return $this->parseFreeFormAttributes($raw, $sanitizer)[0];
        }

        [, $dropped] = $this->parseFreeFormAttributes($raw, AttributeSanitizer::strict());

        if ($dropped && !AttributeSanitizer::isConfigured()) {
            trigger_deprecation(
                'pimcore/pimcore',
                '2026.3',
                'Rendering a DataObject Link with free-form attributes that the stricter policy closing'
                . ' GHSA-h78x-47qg-qjmq would reject. The permissive Link sanitizer default is deprecated and'
                . ' will be removed in 2027.1; set "pimcore.documents.editables.link_sanitizer.strict: true" to'
                . ' reject it now.'
            );
        }

        return $raw;
    }

    /**
     * @return array{0: string, 1: bool} the re-serialized attributes and whether anything was dropped
     */
    private function parseFreeFormAttributes(string $raw, AttributeSanitizer $sanitizer): array
    {
        $pattern = '/\G\s*([A-Za-z_:][-A-Za-z0-9_:.]*)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+)))?(?=\s|$)/';
        $attribs = [];
        $dropped = false;
        $offset = 0;
        $length = strlen($raw);

        while ($offset < $length) {
            if (preg_match($pattern, $raw, $m, PREG_UNMATCHED_AS_NULL, $offset) !== 1) {
                // drop the unparsable token (including leading whitespace) in one step
                if (preg_match('/\G\s*\S+/', $raw, $skipped, 0, $offset) !== 1) {
                    break;
                }
                $offset += strlen($skipped[0]);
                $dropped = true;

                continue;
            }

            $offset += strlen($m[0]);
            if (!$sanitizer->isAttributeKeyAllowed($m[1], true)) {
                $dropped = true;

                continue;
            }

            $value = $m[2] ?? $m[3] ?? $m[4] ?? null;
            $attribs[] = $value === null
                ? $m[1]
                : $m[1] . '="' . htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8', false) . '"';
        }

        return [implode(' ', $attribs), $dropped];
    }

    public function isEmpty(): bool
    {
        $vars = $this->getObjectVars();
        foreach ($vars as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return $this
     */
    public function setValues(array $data = []): static
    {
        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        $this->markMeDirty();

        return $this;
    }

    public function __toString(): string
    {
        return $this->getHtml();
    }

    /**
     * @internal
     *
     * used for non-nullable properties stored with null (legacy data, see PEES-1217)
     *
     * @TODO: Remove in Pimcore 2026
     */
    public function __unserialize(array $data): void
    {
        foreach (get_object_vars($this) as $property => $value) {
            $this->$property = $data["\0*\0" . $property] ?? $value;
        }
    }
}
