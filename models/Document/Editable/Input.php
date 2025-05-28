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

use Pimcore\Model;

/**
 * @method \Pimcore\Model\Document\Editable\Dao getDao()
 */
class Input extends Model\Document\Editable implements EditmodeDataInterface
{
    /**
     * Contains the text for this element
     *
     * @internal
     *
     */
    protected string $text = '';

    public function getType(): string
    {
        return 'input';
    }

    public function getData(): mixed
    {
        return $this->text;
    }

    public function getText(): string
    {
        return $this->getData();
    }

    public function frontend()
    {
        $config = $this->getConfig();

        $text = $this->text;
        if (!isset($config['htmlspecialchars']) || $config['htmlspecialchars'] !== false) {
            $text = htmlspecialchars($this->text);
        }

        return $text;
    }

    public function getDataEditmode(): string
    {
        return htmlentities($this->text);
    }

    public function setDataFromResource(mixed $data): static
    {
        $this->text = $data;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        // this is because the input is now an div contenteditable -> therefore in entities
        $data = html_entity_decode($data, ENT_HTML5 | ENT_QUOTES);
        $this->text = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return !(bool) strlen($this->text);
    }
}
