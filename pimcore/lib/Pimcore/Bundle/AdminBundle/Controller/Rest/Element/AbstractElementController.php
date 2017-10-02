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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest\Element;

use Pimcore\Bundle\AdminBundle\Controller\Rest\AbstractRestController;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Element\AbstractElement;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractElementController extends AbstractRestController
{
    const ELEMENT_DOES_NOT_EXIST = -1;

    /**
     * @param Request $request
     * @param string  $type
     *
     * @return JsonResponse
     */
    protected function inquire(Request $request, $type)
    {
        $table = $type . 's';
        $permission = $type . 's'; // objects

        $this->checkPermission($permission);

        $condense = $request->get('condense');

        if ($request->getMethod() === 'POST') {
            $data = $request->getContent();
            $idList = explode(',', $data);
        } elseif ($request->get('ids')) {
            $idList = explode(',', $request->get('ids'));
        } else {
            $idList = [];
        }

        if ($request->get('id')) {
            $idList[] = $request->get('id');
        }

        if (count($idList) === 0) {
            return $this->createErrorResponse('Missing list of IDs');
        }

        $idList = array_filter($idList, function ($id) {
            return (int)$id;
        });

        $resultData = [];
        foreach ($idList as $id) {
            $resultData[$id] = 0;
        }

        if ($type === 'object') {
            $col = 'o_id';
        } else {
            $col = 'id';
        }

        $connection = $this->get('database_connection');
        $qb = $connection->createQueryBuilder();
        $qb
            ->select($col)
            ->from($table)
            ->where($qb->expr()->in($col, $idList));

        $result = $qb->execute()->fetchAll();

        foreach ($result as $item) {
            $id = $item[$col];

            if ($condense) {
                unset($resultData[$id]);
            } else {
                $resultData[$id] = 1;
            }
        }

        return $this->createSuccessResponse($resultData);
    }

    /**
     * @param AbstractElement $element
     * @param string $type
     *
     * @throws ResponseException
     */
    protected function checkElementPermission(AbstractElement $element, $type)
    {
        $map = [
            'get' => 'view',
            'delete' => 'delete',
            'update' => 'publish',
            'create' => 'create'
        ];

        if (!isset($map[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid permission type: %s', $type));
        }

        $permission = $map[$type];
        if (!$element->isAllowed($permission)) {
            $this->get('monolog.logger.security')->error(
                'User {user} attempted to access {permission} on {elementType} {elementId}, but has no permission to do so',
                [
                    'user' => $this->getUser()->getName(),
                    'permission' => $permission,
                    'elementType' => $element->getType(),
                    'elementId' => $element->getId(),
                ]
            );

            throw new ResponseException($this->createErrorResponse([
                'msg' => sprintf('Not allowed: permission %s is needed', $permission)
            ]));
        }
    }

    /**
     * @param $class
     * @param $data
     *
     * @return \Pimcore\Model\Webservice\Data
     */
    protected function fillWebserviceData($class, $data)
    {
        $wsData = new $class();

        return $this->mapWebserviceData($wsData, $data);
    }

    /**
     * @param $wsData
     * @param $data
     *
     * @return \Pimcore\Model\Webservice\Data
     */
    private function mapWebserviceData($wsData, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $tmp = [];

                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        $object = new \stdClass();
                        $object = $this->mapWebserviceData($object, $subvalue);

                        $tmp[$subkey] = $object;
                    } else {
                        $tmp[$subkey] = $subvalue;
                    }
                }
                $value = $tmp;
            }
            $wsData->$key = $value;
        }

        if ($wsData instanceof \Pimcore\Model\Webservice\Data\DataObject) {
            /** @var \Pimcore\Model\Webservice\Data\DataObject key */
            $wsData->key = \Pimcore\Model\Element\Service::getValidKey($wsData->key, 'object');
        } elseif ($wsData instanceof \Pimcore\Model\Webservice\Data\Document) {
            /** @var \Pimcore\Model\Webservice\Data\Document key */
            $wsData->key = \Pimcore\Model\Element\Service::getValidKey($wsData->key, 'document');
        } elseif ($wsData instanceof \Pimcore\Model\Webservice\Data\Asset) {
            /** @var \Pimcore\Model\Webservice\Data\Asset $wsData */
            $wsData->filename = \Pimcore\Model\Element\Service::getValidKey($wsData->filename, 'asset');
        }

        return $wsData;
    }
}
