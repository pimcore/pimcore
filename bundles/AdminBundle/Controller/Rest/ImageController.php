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

namespace Pimcore\Bundle\AdminBundle\Controller\Rest;

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @deprecated
 */
class ImageController extends AbstractRestController
{
    /**
     * @Route("/image-thumbnail/id/{id}", name="pimcore_api_rest_image_imagethumbnail", methods={"GET"})
     * @Route("/image-thumbnail", name="pimcore_api_rest_image_imagethumbnail_1", methods={"GET"})
     *
     * @param Request     $request
     * @param string|null $id
     *
     * @return JsonResponse
     *
     * @throws ResponseException
     */
    public function imageThumbnailAction(Request $request, $id = null)
    {
        $this->checkPermission('thumbnails');

        $id = $this->resolveId($request, $id);

        $config = Config::getByName($id);
        if (!$config instanceof Config) {
            throw $this->createNotFoundResponseException(sprintf('Thumbnail "%s" doesn\'t exist', htmlentities($id)));
        }

        $data = $config->getForWebserviceExport();

        return $this->createSuccessResponse($data);
    }

    /**
     * @Route("/image-thumbnails", name="pimcore_api_rest_image_imagethumbnails", methods={"GET"})
     */
    public function imageThumbnailsAction()
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list = new Config\Listing();
        $items = $list->getThumbnails();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id' => $item->getName(),
                'text' => $item->getName(),
            ];
        }

        return $this->createCollectionSuccessResponse($thumbnails);
    }
}
