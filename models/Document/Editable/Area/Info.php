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

namespace Pimcore\Model\Document\Editable\Area;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Pimcore\Templating\Model\ViewModelInterface;
use Symfony\Component\HttpFoundation\Request;

class Info
{
    /**
     * @var string
     */
    public $id;

    /**
     *
     * @deprecated since v6.8 and will be removed in 7.
     */
    public $tag;

    /**
     * @var Editable|Editable\Area|Editable\Areablock
     */
    public $editable;

    /**
     * @var array
     */
    public $params;

    /**
     * @var Request
     */
    public $request;

    /**
     * @var ViewModelInterface
     */
    public $view;

    /**
     * @var string
     */
    public $type;

    /**
     * @var int
     */
    public $index;

    public function __construct()
    {
        $this->tag = & $this->editable;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Editable|Editable\Area|Editable\Areablock
     *
     * @deprecated since v6.8 and will be removed in 7. use getEditable() instead.
     */
    public function getTag()
    {
        return $this->getEditable();
    }

    /**
     * @param Editable $tag
     *
     * @deprecated since v6.8 and will be removed in 7. use setEditable() instead.
     */
    public function setTag(Editable $tag)
    {
        $this->setEditable($tag);
    }

    /**
     * @return Editable|Editable\Area|Editable\Areablock
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
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
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
     * @return mixed|null
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
     * @return Request
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
     * @return ViewModelInterface
     *
     * @deprecated
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * @param ViewModelInterface $view
     *
     * @return $this
     *
     * @deprecated
     */
    public function setView(ViewModelInterface $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @return Document|Document\PageSnippet
     */
    public function getDocument()
    {
        $document = null;

        if ($this->view && isset($this->view->document)) {
            $document = $this->view->document;
        } else {
            $document = $this->editable->getDocument();
        }

        return $document;
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

class_alias(Info::class, 'Pimcore\Model\Document\Tag\Area\Info');
