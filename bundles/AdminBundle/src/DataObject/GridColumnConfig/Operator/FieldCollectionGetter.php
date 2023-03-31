<?php
declare(strict_types=1);

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

namespace Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\ResultContainer;
use Pimcore\Model\DataObject\Fieldcollection;
use Pimcore\Model\Element\ElementInterface;

/**
 * @internal
 */
final class FieldCollectionGetter extends AbstractOperator
{
    private string $attr;

    private int $idx;

    private string $colAttr;

    public function __construct(\stdClass $config, array $context = [])
    {
        parent::__construct($config, $context);

        $this->attr = $config->attr ?? '';
        $this->idx = $config->idx ?? 0;
        $this->colAttr = $config->colAttr ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getLabeledValue(array|ElementInterface $element): ResultContainer|\stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->isEmpty = true;

        if (!$this->attr) {
            return $result;
        }

        $getter = 'get' . ucfirst($this->attr);

        /** @var Fieldcollection|null $fc */
        $fc = $element->$getter();

        if ($fc) {
            $item = $fc->get($this->idx);
            if ($item) {
                $itemGetter = 'get' . ucfirst($this->colAttr);
                if (method_exists($item, $itemGetter)) {
                    $value = $item->$itemGetter();
                    $result->value = $value;
                    $result->isEmpty = false;
                } else {
                    $result->value = null;
                    $result->isEmpty = true;
                }
            }
        }

        return $result;
    }
}
