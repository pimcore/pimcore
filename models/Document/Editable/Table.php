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
class Table extends Model\Document\Editable
{
    /**
     * Contains the text for this element
     *
     * @internal
     *
     */
    protected array $data = [];

    public function getType(): string
    {
        return 'table';
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function frontend()
    {
        $html = '';

        if (count($this->data) > 0) {
            $html .= '<table border="0" cellpadding="0" cellspacing="0">';

            foreach ($this->data as $row) {
                $html .= '<tr>';
                foreach ($row as $col) {
                    $html .= '<td>';
                    $html .= $col;
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }

        return $html;
    }

    public function setDataFromResource(mixed $data): static
    {
        $unserializedData = $this->getUnserializedData($data) ?? [];
        $this->data = $unserializedData;

        return $this;
    }

    public function setDataFromEditmode(mixed $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }
}
