<?php

namespace Pimcore\Bundle\EcommerceFrameworkBundle\Model;
use Pimcore\Model\DataObject;

/**
 * Interface LinkGeneratorAwareInterface
 */
interface LinkGeneratorAwareInterface
{

    /**
     * @return DataObject\ClassDefinition\LinkGeneratorInterface|null
     */
    public function getLinkGenerator(): ?DataObject\ClassDefinition\LinkGeneratorInterface;
}
