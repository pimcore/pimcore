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
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Log;

use Monolog\Logger;
use Pimcore\Log\Handler\ApplicationLoggerDb;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Element\Service;
use Psr\Log\LoggerInterface;

class ApplicationLogger implements LoggerInterface
{
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
     * @param bool $initDbHandler
     *
     * @return ApplicationLogger
     */
    public static function getInstance($component = 'default', $initDbHandler = false)
    {
        $container = \Pimcore::getContainer();
        $containerId = 'pimcore.app_logger.' . $component;

        if ($container->has($containerId)) {
            $logger = $container->get($containerId);
        } else {
            $logger = new self;
            if ($initDbHandler) {
                $logger->addWriter($container->get(ApplicationLoggerDb::class));
            }

            $container->set($containerId, $logger);
        }

        $logger->setComponent($component);

        return $logger;
    }

    /**
     * @param object $writer
     */
    public function addWriter($writer)
    {
        if ($writer instanceof \Monolog\Handler\HandlerInterface) {
            if (!isset($this->loggers['default-monolog'])) {
                // auto init Monolog logger
                $this->loggers['default-monolog'] = new \Monolog\Logger('app');
            }
            $this->loggers['default-monolog']->pushHandler($writer);
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
     * @deprecated
     *
     * @param \Pimcore\Log\FileObject | string $fileObject
     */
    public function setFileObject($fileObject)
    {
        $this->fileObject = $fileObject;
    }

    /**
     * @deprecated
     *
     * @param \Pimcore\Model\DataObject\AbstractObject|\Pimcore\Model\Document|\Pimcore\Model\Asset|int $relatedObject
     */
    public function setRelatedObject($relatedObject)
    {
        $this->relatedObject = $relatedObject;

        if ($this->relatedObject instanceof \Pimcore\Model\DataObject\AbstractObject) {
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
     */
    public function log($level, $message, array $context = [])
    {
        if (!isset($context['component']) || is_null($context['component'])) {
            $context['component'] = $this->component;
        }

        if (!isset($context['fileObject']) && $this->fileObject) {
            $context['fileObject'] = $this->fileObject;
            $this->fileObject = null;
        }

        if (isset($context['fileObject'])) {
            if (is_string($context['fileObject'])) {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']);
            } else {
                $context['fileObject'] = str_replace(PIMCORE_PROJECT_ROOT, '', $context['fileObject']->getFilename());
            }
        }

        $relatedObject = null;
        if (isset($context['relatedObject'])) {
            $relatedObject = $context['relatedObject'];
        }

        if (!$relatedObject && $this->relatedObject) {
            $relatedObject = $this->relatedObject;
        }

        if (is_numeric($relatedObject)) {
            $context['relatedObject'] = $relatedObject;
            $context['relatedObjectType'] = $this->relatedObjectType;
        } elseif ($relatedObject instanceof ElementInterface) {
            $context['relatedObject'] = $relatedObject->getId();
            $context['relatedObjectType'] = Service::getElementType($relatedObject);
        }

        $context['source'] = $this->resolveLoggingSource();

        foreach ($this->loggers as $logger) {
            if ($logger instanceof \Psr\Log\LoggerInterface) {
                $logger->log($level, $message, $context);
            }
        }
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
            'alert', 'warning', 'notice', 'info', 'debug',
        ];

        $previousCall = null;
        $logCall = null;

        // look for the first call to this class calling one of the logging methods
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        for ($i = count($backtrace) - 1; $i >= 0; $i--) {
            $previousCall = $logCall;
            $logCall = $backtrace[$i];

            if (!empty($logCall['class']) && $logCall['class'] === __CLASS__) {
                if (in_array($logCall['function'], $validMethods)) {
                    break;
                }
            }
        }

        $normalizeFile = function ($filename) {
            return str_replace(PIMCORE_PROJECT_ROOT . '/', '', $filename);
        };

        $source = '';
        if (null !== $previousCall) {
            if (isset($previousCall['class'])) {
                // called from a class method
                // ClassName->methodName():line
                $source = sprintf(
                    '%s::%s:%d',
                    $previousCall['class'],
                    $previousCall['function'],
                    $logCall['line']
                );
            } else {
                // called from a function
                // filename.php::functionName():line
                $source = sprintf(
                    '%s::%s:%d',
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
     * @param array $context
     */
    public function emergency($message, array $context = [])
    {
        $this->handleLog('emergency', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = [])
    {
        $this->handleLog('critical', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = [])
    {
        $this->handleLog('error', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = [])
    {
        $this->handleLog('alert', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = [])
    {
        $this->handleLog('warning', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = [])
    {
        $this->handleLog('notice', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = [])
    {
        $this->handleLog('info', $message, func_get_args());
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = [])
    {
        $this->handleLog('debug', $message, func_get_args());
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $params
     */
    protected function handleLog($level, $message, $params)
    {
        $context = [];

        if (isset($params[1])) {
            if (is_array($params[1])) {
                // standard PSR-3 -> $context is an array
                $context = $params[1];
            } elseif ($params[1] instanceof \Pimcore\Model\Element\ElementInterface) {
                $context['relatedObject'] = $params[1];
            }
        }

        if (isset($params[2])) {
            if ($params[2] instanceof FileObject) {
                $context['fileObject'] = $params[2];
            }
        }

        if (isset($params[3])) {
            if (is_string($params[3])) {
                $context['component'] = $params[3];
            }
        }

        $this->log($level, $message, $context);
    }

    /**
     * @param string $message
     * @param \Throwable $exceptionObject
     * @param string $priority
     * @param \Pimcore\Model\DataObject\AbstractObject|null $relatedObject
     * @param string|null $component
     */
    public function logException($message, $exceptionObject, $priority = 'alert', $relatedObject = null, $component = null)
    {
        if (is_null($priority)) {
            $priority = 'alert';
        }

        $message .= ' : '.$exceptionObject->getMessage();

        $fileObject = self::createExceptionFileObject($exceptionObject);

        $this->log($priority, $message, [
            'relatedObject' => $relatedObject,
            'fileObject' => $fileObject,
            'component' => $component,
         ]);
    }

    /**
     * Logs a throwable to a given logger. This can be used to format an exception in the same format
     * as the logException method to any PSR/monolog logger (e.g. when consumed via DI)
     *
     * @param LoggerInterface $logger
     * @param string $message
     * @param \Throwable $exception
     * @param mixed $level
     * @param \Pimcore\Model\DataObject\AbstractObject|null $relatedObject
     * @param array $context
     */
    public static function logExceptionObject(
        LoggerInterface $logger,
        string $message,
        \Throwable $exception,
        $level = Logger::ALERT,
        $relatedObject = null,
        array $context = []
    ) {
        $message .= ' : ' . $exception->getMessage();

        $fileObject = self::createExceptionFileObject($exception);

        $logger->log($level, $message, array_merge([
            'relatedObject' => $relatedObject,
            'fileObject' => $fileObject,
        ], $context));
    }

    private static function createExceptionFileObject(\Throwable $exceptionObject)
    {
        //workaround to prevent "nesting level to deep" errors when used var_export()
        ob_start();
        var_dump($exceptionObject);
        $dataDump = ob_get_clean();

        if (!$dataDump) {
            $dataDump = $exceptionObject->getMessage();
        }

        return new FileObject($dataDump);
    }
}
