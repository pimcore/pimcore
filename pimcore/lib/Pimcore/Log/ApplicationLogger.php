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
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Log;

use Psr\Log\LoggerInterface;
use Pimcore\Logger;

class ApplicationLogger /*implements LoggerInterface*/
{

    // we cannot implement LoggerInterface because then we wouldn't able to be compatible with the old logger
    // but we are definitely PSR-3 compatible

    /**
     * @var null
     */
    protected $component = null;

    /**
     * @var null
     */
    protected $fileObject = null;

    /**
     * @var null
     */
    protected $relatedObject = null;

    /**
     * @var string
     */
    protected $relatedObjectType = 'object';

    /**
     * @var array
     */
    protected $loggers = [];

    /**
     * @var array
     */
    protected static $instances = [];

    /**
     * @param string $component
     * @param boolean $initDbHandler
     * @return ApplicationLogger
     */
    public static function getInstance($component = "default", $initDbHandler = false)
    {
        if (array_key_exists($component, self::$instances)) {
            return self::$instances[$component];
        }

        $logger = new self;
        $logger->setComponent($component);

        if ($initDbHandler) {
            $logger->addWriter(new \Pimcore\Log\Handler\ApplicationLoggerDb());
        }

        self::$instances[$component] = $logger;

        return $logger;
    }

    /**
     * Shorthand to get a Db Logger
     *
     * @param string $component
     * @param string $logLevel
     *
     * @return static
     * @throws \Zend_Log_Exception
     */
    public static function getDbLogger($component = null, $logLevel = "error")
    {
        $logger = self::getInstance($component, true);

        return $logger;
    }

    /**
     * @param $writer
     */
    public function addWriter($writer)
    {
        if ($writer instanceof \Zend_Log_Writer_Abstract) {
            // ZF compatibility
            if (!isset($this->loggers["default-zend"])) {
                // auto init Monolog logger
                $this->loggers["default-zend"] = new \Zend_Log();
            }
            $this->loggers["default-zend"]->addWriter($writer);
        } elseif ($writer instanceof \Monolog\Handler\HandlerInterface) {
            if (!isset($this->loggers["default-monolog"])) {
                // auto init Monolog logger
                $this->loggers["default-monolog"] = new \Monolog\Logger('app');
            }
            $this->loggers["default-monolog"]->pushHandler($writer);
        } elseif ($writer instanceof \Psr\Log\LoggerInterface) {
            $this->loggers[] = $writer;
        }
    }



    /**
     * @param string $component
     */
    public function setComponent($component)
    {
        $this->component = $component;
    }

    /**
     * @param \Pimcore\Log\FileObject | string $fileObject
     */
    public function setFileObject($fileObject)
    {
        $this->fileObject = $fileObject;
    }

    /**
     * @param \\Pimcore\Model\Object\AbstractObject | \Pimcore\Model\Document | \Pimcore\Model\Asset | int $relatedObject
     */
    public function setRelatedObject($relatedObject)
    {
        $this->relatedObject = $relatedObject;

        if ($this->relatedObject instanceof \Pimcore\Model\Object\AbstractObject) {
            $this->relatedObjectType = 'object';
        } elseif ($this->relatedObject instanceof \Pimcore\Model\Asset) {
            $this->relatedObjectType = 'asset';
        } elseif ($this->relatedObject instanceof \Pimcore\Model\Document) {
            $this->relatedObjectType = 'document';
        } else {
            $this->relatedObjectType = 'object';
        }
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($context["component"])) {
            $context["component"] = $this->component;
        }

        if (!isset($context["fileObject"]) && $this->fileObject) {
            $context["fileObject"] = $this->fileObject;
        }

        if (isset($context["fileObject"])) {
            if (is_string($context["fileObject"])) {
                $context["fileObject"] = str_replace(PIMCORE_DOCUMENT_ROOT, '', $context["fileObject"]);
            } else {
                $context["fileObject"] = str_replace(PIMCORE_DOCUMENT_ROOT, '', $context["fileObject"]->getFilename());
            }
        }

        $relatedObject = null;
        if (isset($context["relatedObject"])) {
            $relatedObject = $context["relatedObject"];
        }

        if (!$relatedObject && $this->relatedObject) {
            $relatedObject = $this->relatedObject;
        }

        if ($relatedObject) {
            if ($relatedObject instanceof \Pimcore\Model\Object\AbstractObject or $relatedObject instanceof \Pimcore\Model\Document or $relatedObject instanceof \Pimcore\Model\Asset) {
                $relatedObject = $relatedObject->getId();
            }
            if (is_numeric($relatedObject)) {
                $context["relatedObject"] = $relatedObject;
                $context["relatedObjectType"] = $this->relatedObjectType;
            }
        }

        $context['source'] = $this->resolveLoggingSource();

        foreach ($this->loggers as $logger) {
            if ($logger instanceof \Psr\Log\LoggerInterface) {
                $logger->log($level, $message, $context);
            } elseif ($logger instanceof \Zend_Log) {
                // zf compatibility
                $zendLoggerPsr3Mapping = array_flip(Logger::getZendLoggerPsr3Mapping());
                $prio = $zendLoggerPsr3Mapping[$level];
                $logger->log($message, $prio, $context);
            }
        }

        return null;
    }

    /**
     * Resolve logging source
     *
     * @return string
     */
    protected function resolveLoggingSource()
    {
        $validMethods = [
            'log', 'logException', 'emergency', 'critical', 'error',
            'alert', 'warning', 'notice', 'info', 'debug'
        ];

        $previousCall = null;
        $logCall      = null;

        // look for the first call to this class calling one of the logging methods
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        for ($i = count($backtrace) - 1; $i >= 0; $i--) {
            $previousCall = $logCall;
            $logCall      = $backtrace[$i];

            if (!empty($logCall['class']) && $logCall['class'] === __CLASS__) {
                if (in_array($logCall['function'], $validMethods)) {
                    break;
                }
            }
        }

        $normalizeFile = function ($filename) {
            return str_replace(PIMCORE_DOCUMENT_ROOT . '/', '', $filename);
        };

        $source = '';
        if (null !== $previousCall) {
            if (isset($previousCall['class'])) {
                // called from a class method
                // ClassName->methodName():line
                $source = sprintf(
                    '%s%s%s():%d',
                    $previousCall['class'],
                    $previousCall['type'],
                    $previousCall['function'],
                    $logCall['line']
                );
            } else {
                // called from a function
                // filename.php::functionName():line
                $source = sprintf(
                    '%s::%s():%d',
                    $normalizeFile($previousCall['file']),
                    $previousCall['function'],
                    $logCall['line']
                );
            }
        } else {
            // we don't have a previous call when the logger was directly called
            // from a standalone PHP file (e.g. from a CLI script)
            // filename.php:line
            $source = sprintf(
                '%s:%d',
                $normalizeFile($logCall['file']),
                $logCall['line']
            );
        }

        return $source;
    }

    /**
     * @param string $message
     */
    public function emergency($message)
    {
        $this->handleLog("emergency", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function critical($message)
    {
        $this->handleLog("critical", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function error($message)
    {
        $this->handleLog("error", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function alert($message)
    {
        $this->handleLog("alert", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function warning($message)
    {
        $this->handleLog("warning", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function notice($message)
    {
        $this->handleLog("notice", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function info($message)
    {
        $this->handleLog("info", $message, func_get_args());
    }

    /**
     * @param string $message
     */
    public function debug($message)
    {
        $this->handleLog("debug", $message, func_get_args());
    }

    /**
     * @param $level
     * @param $message
     * @param $params
     */
    protected function handleLog($level, $message, $params)
    {
        $context = [];

        if (isset($params[1])) {
            if (is_array($params[1])) {
                // standard PSR-3 -> $context is an array
                $context = $params[1];
            } elseif ($params[1] instanceof \Pimcore\Model\Element\ElementInterface) {
                $context["relatedObject"] = $params[1];
            }
        }

        if (isset($params[2])) {
            if ($params[2] instanceof \Pimcore\Log\FileObject) {
                $context["fileObject"] = $params[2];
            }
        }

        if (isset($params[3])) {
            if (is_string($params[3])) {
                $context["component"] = $params[3];
            }
        }

        $this->log($level, $message, $context);
    }

    /**
     * @param $message
     * @param $exceptionObject
     * @param string $priority
     * @param null $relatedObject
     * @param null $component
     */
     public function logException($message, $exceptionObject, $priority = "alert", $relatedObject = null, $component = null)
     {
         if (is_null($priority)) {
             $priority = Zend_Log::ALERT;
         }

         $message .= ' : '.$exceptionObject->getMessage();

         //workaround to prevent "nesting level to deep" errors when used var_export()
         ob_start();
         var_dump($exceptionObject);
         $dataDump = ob_get_clean();

         if (!$dataDump) {
             $dataDump = $exceptionObject->getMessage();
         }

         $fileObject = new \Pimcore\Log\FileObject($dataDump);

         $this->log($priority, $message, [
             "relatedObject" => $relatedObject,
             "fileObject" => $fileObject,
             "component" => $component
         ]);
     }
}
