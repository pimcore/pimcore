<?php

namespace Pimcore\Event;

final class ObjectClassDefinitionEvents
{
    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const PRE_ADD = 'pimcore.class.preAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const POST_ADD = 'pimcore.class.postAdd';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const PRE_UPDATE = 'pimcore.class.preUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const POST_UPDATE = 'pimcore.class.postUpdate';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const PRE_DELETE = 'pimcore.class.preDelete';

    /**
     * @Event("Pimcore\Event\Model\Object\ClassDefinitionEvent")
     * @var string
     */
    const POST_DELETE = 'pimcore.class.postDelete';

}