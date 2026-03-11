<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\GlossaryBundle\Controller;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Helper\ParameterBagHelper;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/glossary', name: 'pimcore_bundle_glossary_settings_glossary', methods: ['POST'])]
    public function glossaryAction(Request $request): JsonResponse
    {
        // check glossary permissions
        $this->checkPermission('glossary');

        if ($request->request->has('data')) {
            $data = $this->decodeJson($request->request->getString('data'));

            Cache::clearTag('glossary');

            if ($request->query->getString('xaction') === 'destroy') {
                $id = $data['id'];
                $glossary = Glossary::getById($id);
                $glossary->delete();

                return $this->jsonResponse(['success' => true, 'data' => []]);
            } elseif ($request->query->getString('xaction') === 'update') {
                // save glossary
                $glossary = Glossary::getById($data['id']);

                if (!empty($data['link'])) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if ((int)$link > 0) {
                        if ($doc = Document::getById((int)$link)) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                return $this->jsonResponse(['data' => $glossary, 'success' => true]);
            } elseif ($request->query->getString('xaction') == 'create') {
                unset($data['id']);

                // save glossary
                $glossary = new Glossary();

                if (!empty($data['link'])) {
                    if ($doc = Document::getByPath($data['link'])) {
                        $data['link'] = $doc->getId();
                    }
                }

                $glossary->setValues($data);

                $glossary->save();

                if ($link = $glossary->getLink()) {
                    if ((int)$link > 0) {
                        if ($doc = Document::getById((int)$link)) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                return $this->jsonResponse(['data' => $glossary->getObjectVars(), 'success' => true]);
            }
        } else {
            $list = new Glossary\Listing();
            $list->setLimit(ParameterBagHelper::getInt($request->request, 'limit', 50));
            $list->setOffset(ParameterBagHelper::getInt($request->request, 'start'));

            $sortingSettings = self::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
            if ($sortingSettings['orderKey']) {
                $list->setOrderKey($sortingSettings['orderKey']);
                $list->setOrder($sortingSettings['order']);
            }

            if ($request->request->has('filter')) {
                $list->setCondition('`text` LIKE ' . $list->quote('%'.$request->request->getString('filter').'%'));
            }

            $list->load();

            $glossaries = [];
            foreach ($list->getGlossary() as $glossary) {
                if ($link = $glossary->getLink()) {
                    if ((int)$link > 0) {
                        if ($doc = Document::getById((int)$link)) {
                            $glossary->setLink($doc->getRealFullPath());
                        }
                    }
                }

                $glossaries[] = $glossary->getObjectVars();
            }

            return $this->jsonResponse(['data' => $glossaries, 'success' => true, 'total' => $list->getTotalCount()]);
        }

        return $this->jsonResponse(['success' => false]);
    }

    private static function extractSortingSettings(array $params): array
    {
        $orderKey = null;
        $order = null;

        $sortParam = $params['sort'] ?? false;
        if ($sortParam) {
            $sortParam = json_decode($sortParam, true);
            $sortParam = $sortParam[0];

            $order = strtoupper($sortParam['direction']) === 'DESC' ? 'DESC' : 'ASC';

            if (!str_starts_with($sortParam['property'], '~')) {
                $orderKey = $sortParam['property'];
            } else {
                $orderKey = $sortParam['property'];
                $parts = explode('~', $orderKey);
                $fieldname = $parts[2];
                $groupKeyId = $parts[3];
                $groupKeyId = explode('-', $groupKeyId);
                $groupId = (int) $groupKeyId[0];
                $keyid = (int) $groupKeyId[1];

                return ['orderKey' => $sortParam['property'], 'fieldname' => $fieldname, 'groupId' => $groupId, 'keyId' => $keyid, 'order' => $order, 'isFeature' => 1];
            }
        }

        return ['orderKey' => $orderKey, 'order' => $order];
    }
}
