<?php


namespace Pimcore\Event\Model\Ecommerce\IndexService;

use Pimcore\Bundle\EcommerceFrameworkBundle\IndexService\Config\Definition\Attribute;
use Symfony\Component\EventDispatcher\Event;

class PreprocessErrorEvent extends Event
{
    private $exception;

    /**
     * PreprocessErrorEvent constructor.
     * @param \Exception $exception
     */
    public function __construct(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return \Exception
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }

}