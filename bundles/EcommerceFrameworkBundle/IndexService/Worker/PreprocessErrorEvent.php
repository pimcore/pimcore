<?php


namespace Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Worker;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Symfony\Component\EventDispatcher\Event;

class PreprocessErrorEvent extends Event
{
    private $attribute;
    private $exception;

    private $skipAttribute = true; //skip attribute is currently the default behavior.

    public function __construct(Attribute $attribute, \Exception $exception)
    {
        $this->attribute = $attribute;
        $this->exception = $exception;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
    }

    /**
     * @return \Exception
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }

    /**
     * @return bool
     */
    public function isSkipAttribute(): bool
    {
        return $this->skipAttribute;
    }

    /**
     * @param bool $skipAttribute
     * @return PreprocessErrorEvent
     */
    public function setSkipAttribute(bool $skipAttribute): PreprocessErrorEvent
    {
        $this->skipAttribute = $skipAttribute;
        return $this;
    }




}