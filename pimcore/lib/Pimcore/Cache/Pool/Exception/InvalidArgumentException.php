<?php

namespace Pimcore\Cache\Pool\Exception;

use Psr\Cache\InvalidArgumentException as InvalidArgumentExceptionInterface;

class InvalidArgumentException extends \InvalidArgumentException implements InvalidArgumentExceptionInterface
{
}
