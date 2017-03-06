<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller\Rest;

use Pimcore\Bundle\PimcoreAdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\PimcoreBundle\Http\Exception\ResponseException;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

class ImageController extends AbstractRestController
{
    /**
     * @Method("GET")
     * @Route("/image-thumbnail/id/{id}")
     * @Route("/image-thumbnail")
     *
     * @param Request     $request
     * @param string|null $id
     *
     * @return JsonResponse
     * @throws ResponseException
     */
    public function imageThumbnailAction(Request $request, $id = null)
    {
        $this->checkPermission('thumbnails');

        $id = $this->resolveId($request, $id);

        $config = Config::getByName($id);
        if (!$config instanceof Config) {
            throw $this->createNotFoundException(sprintf('Thumbnail "%s" doesn\'t exist', htmlentities($id)));
        }

        return $this->createSuccessResponse([
            'data' => $config->getForWebserviceExport()
        ]);
    }

    /**
     * @Method("GET")
     * @Route("/image-thumbnails")
     */
    public function imageThumbnailsAction()
    {
        $this->checkPermission('thumbnails');

        $thumbnails = [];

        $list  = new Config\Listing();
        $items = $list->load();

        foreach ($items as $item) {
            $thumbnails[] = [
                'id'   => $item->getName(),
                'text' => $item->getName()
            ];
        }

        return $this->createSuccessResponse([
            'data' => $thumbnails
        ]);
    }
}
