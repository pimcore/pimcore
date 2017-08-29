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

namespace Pimcore\WorkflowManagement\Workflow;

use Pimcore\Logger;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\Element\Service;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\WorkflowManagement\Workflow;

class Config
{
    /**
     * @param bool $forceReload
     *
     * @return array|null
     */
    public static function getWorkflowManagementConfig($forceReload = false)
    {
        $config = null;

        if (\Pimcore\Cache\Runtime::isRegistered('pimcore_config_workflowmanagement') && !$forceReload) {
            $config = \Pimcore\Cache\Runtime::get('pimcore_config_workflowmanagement');
        } else {
            try {
                $file = \Pimcore\Config::locateConfigFile('workflowmanagement.php');

                if (is_file($file)) {
                    $config = include($file);

                    if (is_array($config)) {
                        self::setWorkflowManagementConfig($config);
                    } else {
                        Logger::error("$file exists but it is not a valid PHP array configuration.");
                    }
                }
            } catch (\Exception $e) {
                $file = \Pimcore\Config::locateConfigFile('workflowmanagement.php');
                Logger::emergency('Cannot find workflow configuration, should be located at: ' . $file);
            }
        }

        return $config;
    }

    /**
     * @static
     *
     * @param array $config
     */
    public static function setWorkflowManagementConfig($config)
    {
        \Pimcore\Cache\Runtime::set('pimcore_config_workflowmanagement', $config);
    }

    /**
     * gets workflow config for element. always returns first valid workflow config
     *
     * @param AbstractElement $element
     *
     * @return array
     */
    public static function getElementWorkflowConfig(AbstractElement $element)
    {
        $config = self::getWorkflowManagementConfig();
        if (!is_array($config)) {
            return null;
        }

        $elementType = Service::getElementType($element);
        $elementSubType = $element->getType();

        foreach ($config as $id => $workflow) {

            //workflow is not enabled, continue with next
            if (isset($workflow['enabled']) && !$workflow['enabled']) {
                continue;
            }

            if (isset($workflow['workflowSubject']) && in_array($elementType, $workflow['workflowSubject']['types'])) {
                switch ($elementType) {
                    case 'asset':

                        if (isset($workflow['workflowSubject']['assetTypes']) && is_array($workflow['workflowSubject']['assetTypes'])) {
                            if (in_array($elementSubType, $workflow['workflowSubject']['assetTypes'])) {
                                return $workflow;
                            }
                        } else {
                            Logger::warning('WorkflowManagement::getClassWorkflowConfig workflow does not feature a valid array of available asset types');
                        }

                        break;

                    case 'document':

                        if (isset($workflow['workflowSubject']['documentTypes']) && is_array($workflow['workflowSubject']['documentTypes'])) {
                            if (in_array($elementSubType, $workflow['workflowSubject']['documentTypes'])) {
                                return $workflow;
                            }
                        } else {
                            Logger::warning('WorkflowManagement::getClassWorkflowConfig workflow does not feature a valid array of available document types');
                        }

                        break;

                    case 'object':

                        if ($element instanceof ConcreteObject) {
                            if (isset($workflow['workflowSubject']['classes']) && is_array($workflow['workflowSubject']['classes'])) {
                                $classId = $element->getClassId();
                                if (in_array($classId, $workflow['workflowSubject']['classes'])) {
                                    return $workflow;
                                }
                            } else {
                                Logger::warning('WorkflowManagement::getClassWorkflowConfig workflow does not feature a valid array of available class ID\'s');
                            }
                        }

                        break;

                    default:
                        //unknown element type, return null
                        return null;
                }
            }
        }

        return null;
    }

    //    /**
//     * @param $classId
//     * @return null
//     * @throws \Exception
//     */
//    public static function getClassWorkflowConfig($classId)
//    {
//        if ($classId instanceof \Pimcore\Model\DataObject\ClassDefinition) {
//            $classId = $classId->getId();
//        }
//
//        $config = self::getWorkflowManagementConfig();
//
//        foreach ($config['workflows'] as $workflow) {
//
//            if (isset($workflow['type']) && $workflow['type'] !== 'object') {
//                continue;
//            }
//
//            if (isset($workflow['enabled']) && !$workflow['enabled']) {
//                continue;
//            }
//
//            if (isset($workflow['classes']) && is_array($workflow['classes'])) {
//
//                if (in_array($classId, $workflow['classes'])) {
//                    return $workflow;
//                }
//            } else {
//                Logger::warning('WorkflowManagement::getClassWorkflowConfig workflow does not feature a valid array of available class ID\'s');
//            }
//
//        }
//
//        return null;
//    }
}
