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
class Textarea extends Model\Document\Editable
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
        return 'textarea';
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
    public function frontend()
    {
        $config = $this->getConfig();

        $text = $this->text;
        if (!isset($config['htmlspecialchars']) || $config['htmlspecialchars'] !== false) {
            $text = htmlspecialchars($this->text);
        }

        if (isset($config['nl2br']) && $config['nl2br']) {
            $text = nl2br($text);
        }

        return $text;
    }

    public function getDataEditmode()
    {
        return htmlentities($this->text);
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
        $data = html_entity_decode($data, ENT_HTML5); // this is because the input is now an div contenteditable -> therefore in entities
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
}
