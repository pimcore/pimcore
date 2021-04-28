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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
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
     * @var array
     */
    protected $data;

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'table';
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function frontend()
    {
        $html = '';

        if (is_array($this->data) && count($this->data) > 0) {
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

    /**
     * {@inheritdoc}
     */
    public function setDataFromResource($data)
    {
        $this->data = \Pimcore\Tool\Serialize::unserialize($data);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setDataFromEditmode($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty()
    {
        return empty($this->data);
    }
}
