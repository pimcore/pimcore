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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Templating;

use Pimcore\Model\Document;
use Pimcore\Model\Document\Editable;
use Symfony\Bundle\FrameworkBundle\Templating\PhpEngine as BasePhpEngine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\Helper\SlotsHelper;
use Symfony\Component\Templating\Storage\Storage;

/**
 * Symfony PHP engine with pimcore additions:
 *
 *  - property access - $this->variable and $this->helper()
 *  - helper brokers integrate other view helpers (ZF) on __call
 *  - tag integration
 *
 *
 * Pimcore editables
 * @method Editable\Area area($name, $options = [])
 * @method Editable\Areablock areablock($name, $options = [])
 * @method Editable\Block block($name, $options = [])
 * @method Editable\Checkbox checkbox($name, $options = [])
 * @method Editable\Date date($name, $options = [])
 * @method Editable\Embed embed($name, $options = [])
 * @method Editable\Relation relation($name, $options = [])
 * @method Editable\Image image($name, $options = [])
 * @method Editable\Input input($name, $options = [])
 * @method Editable\Link link($name, $options = [])
 * @method Editable\Relations relations($name, $options = [])
 * @method Editable\Multiselect multiselect($name, $options = [])
 * @method Editable\Numeric numeric($name, $options = [])
 * @method Editable\Pdf pdf($name, $options = [])
 * @method Editable\Renderlet renderlet($name, $options = [])
 * @method Editable\Select select($name, $options = [])
 * @method Editable\Snippet snippet($name, $options = [])
 * @method Editable\Table table($name, $options = [])
 * @method Editable\Textarea textarea($name, $options = [])
 * @method Editable\Video video($name, $options = [])
 * @method Editable\Wysiwyg wysiwyg($name, $options = [])
 * @method Editable\Scheduledblock scheduledblock($name, $options = [])
 *
 * @property Document $document
 * @property bool $editmode
 * @property GlobalVariables $app
 *
 * @deprecated since 6.8.0 and will be removed in Pimcore 7.
 */
class PhpEngine extends BasePhpEngine
{
    const PARAM_NO_PARENT = '_no_parent';

    /**
     * @var array
     */
    protected $params = [];

    /**
     * {@inheritdoc}
     */
    protected function evaluate(Storage $template, array $parameters = [])
    {
        $this->params = $parameters;

        // disable parent with "magic" _no_parent parameter
        $disableParent = false;
        if (isset($parameters[static::PARAM_NO_PARENT])) {
            $disableParent = (bool)($parameters[static::PARAM_NO_PARENT]);
            unset($parameters[static::PARAM_NO_PARENT]);
        }

        // render the template
        $result = parent::evaluate($template, $parameters);

        if ($disableParent) {
            $this->parents[$this->current] = null;
        }

        return $result;
    }

    /**
     * Renders template with current parameters
     *
     * @param string $name
     * @param array $parameters
     *
     * @return string
     */
    public function template($name, array $parameters = [])
    {
        return $this->render($name, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function __get($name)
    {
        return $this->params[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function __isset($name)
    {
        return ($this->params[$name] ?? null) ? true : false;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->params[$name] = $value;
    }
}
