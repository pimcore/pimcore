<?php

namespace Pimcore\Model\DataObject\Localizedfield;

use Doctrine\DBAL\Exception\RetryableException;
use Exception;

class LanguageTableDoesNotExistException extends Exception implements RetryableException
{
}
