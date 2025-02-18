<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\SimpleBackendSearchBundle\Controller;

use Pimcore\Controller\UserAwareController;
use Pimcore\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class DataObjectController extends UserAwareController
{
    /**
     * @Route("/relation-objects-list", name="pimcore_bundle_search_dataobject_relation_objects_list", methods={"GET"})
     */
    public function optionsAction(Request $request): JsonResponse
    {
        $fieldConfig = json_decode($request->query->getString('fieldConfig'), true);

        $options = [];
        $classes = [];
        if (count($fieldConfig['classes']) > 0) {
            foreach ($fieldConfig['classes'] as $classData) {
                $classes[] = $classData['classes'];
            }
        }

        $visibleFields = is_array($fieldConfig['visibleFields']) ? $fieldConfig['visibleFields'] : explode(',', $fieldConfig['visibleFields']);

        if (!$visibleFields) {
            $visibleFields = ['id', 'fullpath', 'classname'];
        }

        $searchRequest = $request;
        $searchRequest->request->set('type', 'object');
        $searchRequest->request->set('subtype', 'object,variant');
        $searchRequest->request->set('class', implode(',', $classes));
        $searchRequest->request->set('fields', $visibleFields);

        $searchRequest->attributes->set('unsavedChanges', $request->query->getString('unsavedChanges'));
        $res = $this->forward(SearchController::class.'::findAction', ['request' => $searchRequest]);
        $objects = json_decode($res->getContent(), true)['data'];

        if ($request->query->has('data')) {
            $dataArray = json_decode($request->query->getString('data'), true);
            if (is_array($dataArray)) {
                foreach ($dataArray as $preSelectedElement) {
                    if (isset($preSelectedElement['id'], $preSelectedElement['type'])) {
                        $objects[] = ['id' => $preSelectedElement['id'], 'type' => $preSelectedElement['type']];
                    }
                }
            }
        }

        foreach ($objects as $objectData) {
            $option = [
                'id' => $objectData['id'],
                'type' => $objectData['type'],
            ];

            $visibleFieldValues = [];
            foreach ($visibleFields as $visibleField) {
                if (isset($objectData[$visibleField])) {
                    $visibleFieldValues[] = $objectData[$visibleField];
                } else {
                    $fallbackValues = DataObject\Localizedfield::getGetFallbackValues();
                    DataObject\Localizedfield::setGetFallbackValues(true);

                    $visibleFieldValue = DataObject\Service::useInheritedValues(true, static function () use ($objectData, $visibleField, $classes) {
                        $object = DataObject\Concrete::getById($objectData['id']);
                        if (!$object instanceof DataObject\Concrete) {
                            return null;
                        }

                        $getter = 'get'.ucfirst($visibleField);
                        $visibleFieldValue = $object->$getter();
                        if (count($classes) > 1 && $visibleField == 'key') {
                            $visibleFieldValue .= ' ('.$object->getClassName().')';
                        }

                        return $visibleFieldValue;
                    });
                    if (!$visibleFieldValue) {
                        continue;
                    }
                    $visibleFieldValues[] = $visibleFieldValue;

                    DataObject\Localizedfield::setGetFallbackValues($fallbackValues);
                }
            }

            $option['label'] = implode(', ', $visibleFieldValues);

            $options[] = $option;
        }

        return new JsonResponse($options);
    }
}
