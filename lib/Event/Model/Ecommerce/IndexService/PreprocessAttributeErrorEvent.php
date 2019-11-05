<?php


namespace Pimcore\Event\Model\Ecommerce\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Symfony\Component\EventDispatcher\Event;

class PreprocessAttributeErrorEvent extends PreprocessErrorEvent
{
    /**
     * @var Attribute
     */
    private $attribute;

    private $skipAttribute = true; //skip attribute is currently the default behavior.

    /**
     * PreprocessAttributeErrorEvent constructor.
     * @param Attribute $attribute
     * @param \Exception $exception
     */
    public function __construct(Attribute $attribute, \Exception $exception)
    {
        parent::__construct($exception);
        $this->attribute = $attribute;
    }

    /**
     * @return Attribute
     */
    public function getAttribute(): Attribute
    {
        return $this->attribute;
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