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
 * @package    Object
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\DataObject\GridColumnConfig\Operator;

use Pimcore\Model\DataObject\Objectbrick;

/**
 * Class ObjectBrickGetter
 *
 * @deprecated ObjectBrickGetter operator is deprecated since version 6.7 and will be removed in 7.0.0
 */
class ObjectBrickGetter extends AbstractOperator
{
    /** @var string */
    private $brickAttr;

    /** @var string */
    private $brickType;

    /** @var string */
    private $attr;

    public function __construct(\stdClass $config, $context = null)
    {
        parent::__construct($config, $context);

        @trigger_error(
            'ObjectBrickGetter operator is deprecated since version 6.7 and will be removed in 7.0.0',
            E_USER_DEPRECATED
        );

        $this->attr = $config->attr ?? '';
        $this->brickType = $config->brickType ?? '';
        $this->brickAttr = $config->brickAttr ?? '';
    }

    public function getLabeledValue($element)
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isEmpty = true;

        if (!$this->attr) {
            return null;
        }

        $bricksGetter = 'get' . ucfirst($this->attr);

        $bricks = $element->$bricksGetter();

        if ($bricks instanceof Objectbrick && $this->brickType) {
            $brickGetter = 'get' . ucfirst($this->brickType);
            $brick = $bricks->$brickGetter();
            if ($brick) {
                $brickAttrGetter = 'get' . ucfirst($this->brickAttr);
                $value = $brick->$brickAttrGetter();
                $result->value = $value;
                $result->isEmpty = false;
            }
        }

        return $result;
    }
}
