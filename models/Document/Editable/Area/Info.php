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

namespace Pimcore\Model\Document\Editable\Area;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Symfony\Component\HttpFoundation\Request;

class Info
{
    /**
     * @internal
     *
     * @var string|null
     */
    protected $id;

    /**
     * @internal
     *
     * @var Editable|null
     */
    protected $editable;

    /**
     * @internal
     *
     * @var array
     */
    protected $params = [];

    /**
     * @internal
     *
     * @var Request|null
     */
    protected $request;

    /**
     * @internal
     *
     * @var string|null
     */
    protected $type;

    /**
     * @internal
     *
     * @var int|null
     */
    protected $index;

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Editable|null
     */
    public function getEditable()
    {
        return $this->editable;
    }

    /**
     * @param Editable $editable
     */
    public function setEditable(Editable $editable)
    {
        $this->editable = $editable;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getParam(string $name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @return $this
     */
    public function setParam(string $name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     *
     * @return $this
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param int|null $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return Document\PageSnippet
     */
    public function getDocument()
    {
        return $this->editable->getDocument();
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return Editable|null
     *
     * @throws \Exception
     */
    public function getDocumentElement($name, $type = '')
    {
        $editable = null;
        $document = $this->getDocument();

        if ($document instanceof Document\PageSnippet) {
            $name = Editable::buildEditableName($type, $name, $document);
            $editable = $document->getEditable($name);
        }

        return $editable;
    }
}
