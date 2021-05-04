<?php

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
 *  @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\AdminBundle\Controller\Admin\DataObject;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\DataObject\Data\QuantityValue;
use Pimcore\Model\DataObject\QuantityValue\Unit;
use Pimcore\Model\DataObject\QuantityValue\UnitConversionService;
use Pimcore\Model\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 *  @internal
 */
class QuantityValueController extends AdminController
{
    /**
     * @Route("/quantity-value/unit-proxy", name="pimcore_admin_dataobject_quantityvalue_unitproxyget", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function unitProxyGetAction(Request $request)
    {
        $list = new Unit\Listing();

        $order = ['ASC', 'ASC', 'ASC'];
        $orderKey = ['baseunit', 'factor', 'abbreviation'];

        $allParams = array_merge($request->request->all(), $request->query->all());
        $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($allParams);

        // Prepend user-requested sorting settings but keep the others to keep secondary order of quantity values in respective order
        if ($sortingSettings['orderKey']) {
            array_unshift($orderKey, $sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            array_unshift($order, $sortingSettings['order']);
        }

        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $list->setLimit($request->get('limit'));
        $list->setOffset($request->get('start'));

        $condition = '1 = 1';
        if ($request->get('filter')) {
            $filterString = $request->get('filter');
            $filters = json_decode($filterString);
            $db = \Pimcore\Db::get();
            foreach ($filters as $f) {
                if ($f->type == 'string') {
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
                } elseif ($f->type == 'numeric') {
                    $operator = $this->getOperator($f->comparison);
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' ' . $operator . ' ' . $db->quote($f->value);
                }
            }
            $list->setCondition($condition);
        }

        $units = [];
        foreach ($list->getUnits() as $u) {
            $units[] = $u->getObjectVars();
        }

        return $this->adminJson(['data' => $units, 'success' => true, 'total' => $list->getTotalCount()]);
    }

    /**
     * @Route("/quantity-value/unit-proxy", name="pimcore_admin_dataobject_quantityvalue_unitproxy", methods={"POST", "PUT"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Exception
     */
    public function unitProxyAction(Request $request)
    {
        if ($request->get('data')) {
            if ($request->get('xaction') == 'destroy') {
                $data = json_decode($request->get('data'), true);
                $id = $data['id'];
                $unit = \Pimcore\Model\DataObject\QuantityValue\Unit::getById($id);
                if (!empty($unit)) {
                    $unit->delete();

                    return $this->adminJson(['data' => [], 'success' => true]);
                } else {
                    throw new \Exception('Unit with id ' . $id . ' not found.');
                }
            } elseif ($request->get('xaction') == 'update') {
                $data = json_decode($request->get('data'), true);
                $unit = Unit::getById($data['id']);
                if (!empty($unit)) {
                    if (($data['baseunit'] ?? null) === -1) {
                        $data['baseunit'] = null;
                    }
                    $unit->setValues($data);
                    $unit->save();

                    return $this->adminJson(['data' => $unit->getObjectVars(), 'success' => true]);
                } else {
                    throw new \Exception('Unit with id ' . $data['id'] . ' not found.');
                }
            } elseif ($request->get('xaction') == 'create') {
                $data = json_decode($request->get('data'), true);
                if (isset($data['baseunit']) && $data['baseunit'] === -1) {
                    $data['baseunit'] = null;
                }

                $id = $data['id'];
                if (Unit::getById($id)) {
                    throw new \Exception('unit with ID [' . $id . '] already exists');
                }

                $unit = new Unit();
                $unit->setValues($data);
                $unit->save();

                return $this->adminJson(['data' => $unit->getObjectVars(), 'success' => true]);
            }
        }

        return $this->adminJson(['success' => false]);
    }

    /**
     * @param string $comparison
     *
     * @return string
     */
    private function getOperator($comparison)
    {
        $mapper = [
            'lt' => '<',
            'gt' => '>',
            'eq' => '=',
        ];

        return $mapper[$comparison];
    }

    /**
     * @Route("/quantity-value/unit-list", name="pimcore_admin_dataobject_quantityvalue_unitlist", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function unitListAction(Request $request)
    {
        $list = new Unit\Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);
        if ($request->get('filter')) {
            $array = explode(',', $request->get('filter'));
            $quotedArray = [];
            $db = \Pimcore\Db::get();
            foreach ($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $string = implode(',', $quotedArray);
            $list->setCondition('id IN (' . $string . ')');
        }

        $result = [];
        $units = $list->getUnits();
        foreach ($units as &$unit) {
            try {
                if ($unit->getAbbreviation()) {
                    $unit->setAbbreviation(\Pimcore\Model\Translation::getByKeyLocalized($unit->getAbbreviation(), Translation::DOMAIN_ADMIN,
                        true, true));
                }
                if ($unit->getLongname()) {
                    $unit->setLongname(\Pimcore\Model\Translation::getByKeyLocalized($unit->getLongname(), Translation::DOMAIN_ADMIN, true,
                        true));
                }
                $result[] = $unit->getObjectVars();
            } catch (\Exception $e) {
                // nothing to do ...
            }
        }

        return $this->adminJson(['data' => $result, 'success' => true, 'total' => $list->getTotalCount()]);
    }

    /**
     * @Route("/quantity-value/convert", name="pimcore_admin_dataobject_quantityvalue_convert", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function convertAction(Request $request)
    {
        $fromUnitId = $request->get('fromUnit');
        $toUnitId = $request->get('toUnit');

        $fromUnit = Unit::getById($fromUnitId);
        $toUnit = Unit::getById($toUnitId);
        if (!$fromUnit instanceof Unit || !$toUnit instanceof Unit) {
            return $this->adminJson(['success' => false]);
        }

        /** @var UnitConversionService $converter */
        $converter = $this->container->get(UnitConversionService::class);
        try {
            $convertedValue = $converter->convert(new QuantityValue($request->get('value'), $fromUnit), $toUnit);
        } catch (\Exception $e) {
            return $this->adminJson(['success' => false]);
        }

        return $this->adminJson(['value' => $convertedValue->getValue(), 'success' => true]);
    }

    /**
     * @Route("/quantity-value/convert-all", name="pimcore_admin_dataobject_quantityvalue_convertall", methods={"GET"})
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function convertAllAction(Request $request)
    {
        $unitId = $request->get('unit');

        $fromUnit = Unit::getById($unitId);
        if (!$fromUnit instanceof Unit) {
            return $this->adminJson(['success' => false]);
        }

        $baseUnit = $fromUnit->getBaseunit() ?? $fromUnit;

        $units = new Unit\Listing();
        $units->setCondition('baseunit = '.$units->quote($baseUnit->getId()).' AND id != '.$units->quote($fromUnit->getId()));

        $convertedValues = [];
        /** @var UnitConversionService $converter */
        $converter = $this->container->get(UnitConversionService::class);
        foreach ($units->getUnits() as $targetUnit) {
            try {
                $convertedValue = $converter->convert(new QuantityValue($request->get('value'), $fromUnit), $targetUnit);

                $convertedValues[] = ['unit' => $targetUnit->getAbbreviation(), 'unitName' => $targetUnit->getLongname(), 'value' => round($convertedValue->getValue(), 4)];
            } catch (\Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->adminJson(['value' => $request->get('value'), 'fromUnit' => $fromUnit->getAbbreviation(), 'values' => $convertedValues, 'success' => true]);
    }
}
