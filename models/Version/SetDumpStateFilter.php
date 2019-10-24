<?php

namespace Pimcore\Model\Version;

use DeepCopy\Filter\Filter;
use Pimcore\Model\Element\ElementDumpStateInterface;

/**
 * @final
 */
class SetDumpStateFilter implements Filter
{

    protected $state;
    /**
     * SetDumpStateFilter constructor.
     */
    public function __construct(bool  $state)
    {
        $this->state = $state;
    }


    /**
     * Sets the object property to null.
     *
     * {@inheritdoc}
     */
    public function apply($object, $property, $objectCopier)
    {
        if ($object instanceof ElementDumpStateInterface) {
            $object->setInDumpState($this->state);
        }
    }
}
