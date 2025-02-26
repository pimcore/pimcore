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

namespace Pimcore\Bundle\GlossaryBundle\Controller;

use Pimcore\Bundle\GlossaryBundle\Model\Glossary;
use Pimcore\Cache;
use Pimcore\Controller\Traits\JsonHelperTrait;
use Pimcore\Controller\UserAwareController;
use Pimcore\Extension\Bundle\Exception\AdminClassicBundleNotFoundException;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/settings")
 *
 * @internal
 */
class SettingsController extends UserAwareController
{
    use JsonHelperTrait;

    /**
     * @Route("/glossary", name="pimcore_bundle_glossary_settings_glossary", methods={"POST"})
     *
     *
     */
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
            if (!class_exists(\Pimcore\Bundle\AdminBundle\Helper\QueryParams::class)) {
                throw new AdminClassicBundleNotFoundException('This action requires package "pimcore/admin-ui-classic-bundle" to be installed.');
            }

            $list = new Glossary\Listing();
            $list->setLimit($request->request->getInt('limit', 50));
            $list->setOffset($request->request->getInt('start'));

            $sortingSettings = \Pimcore\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings(array_merge($request->request->all(), $request->query->all()));
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
}
