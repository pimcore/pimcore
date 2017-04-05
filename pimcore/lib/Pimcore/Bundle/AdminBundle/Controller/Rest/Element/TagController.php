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

use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Http\Exception\ResponseException;
use Pimcore\Model\Asset;
use Pimcore\Model\Document;
use Pimcore\Model\Element;
use Pimcore\Model\Object;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TagController extends AbstractElementController
{
    const TAG_DOES_NOT_EXIST = -1;

    /**
     * @Method("GET")
     * @Route("/tag-list")
     *
     * Returns a list of all tags.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/tag-list?apikey=[API-KEY]
     *
     * @return JsonResponse
     */
    public function tagListAction()
    {
        $this->checkPermission('tags_search');

        /** @var Element\Tag\Listing|Element\Tag\Listing\Dao $list */
        $list = new Element\Tag\Listing();
        $tags = $list->load();

        $result = [];

        /** @var Element\Tag $tag */
        foreach ($tags as $tag) {
            $item = [
                'id'       => $tag->getId(),
                'parentId' => $tag->getParentId(),
                'name'     => $tag->getName()
            ];

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Method("GET")
     * @Route("/tags-element-list")
     *
     * Returns a list of all tags for an element.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/tags-element-list?apikey=[API-KEY]&id=1281&type=object
     *
     * Parameters:
     *      - element id
     *      - type of element (document | asset | object)
     *
     * @return JsonResponse
     */
    public function tagsElementListAction(Request $request)
    {
        $this->checkPermission('tags_search');

        $id = $request->get('id');
        if (!$id) {
            return $this->createErrorResponse('Missing tag ID');
        }

        $type = $request->get('type');
        if (!$type) {
            return $this->createErrorResponse('Missing type');
        }

        $this->checkType($type);

        $element = null;
        if ($type === 'document') {
            $element = Document::getById($id);
        } elseif ($type === 'asset') {
            $element = Asset::getById($id);
        } elseif ($type === 'object') {
            $element = Object::getById($id);
        }

        if (!$element) {
            return $this->createErrorResponse([
                'msg'  => 'Element does not exist',
                'code' => static::ELEMENT_DOES_NOT_EXIST
            ], Response::HTTP_NOT_FOUND);
        }

        $this->checkElementPermission($element, 'get');

        $assignedTags = Element\Tag::getTagsForElement($type, $element->getId());

        $result = [];
        foreach ($assignedTags as $tag) {
            $item = [
                'id'       => $tag->getId(),
                'parentId' => $tag->getParentId(),
                'name'     => $tag->getName()
            ];

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @Method("GET")
     * @Route("/elements-tag-list")
     *
     * Returns a list of elements id/type pairs for a tag.
     *  GET http://[YOUR-DOMAIN]/webservice/rest/elements-tag-list?apikey=[API-KEY]&id=12&type=object
     *
     * Parameters:
     *      - tag id
     *      - type of element (document | asset | object)
     *
     * @return JsonResponse
     */
    public function elementsTagListAction(Request $request)
    {
        $this->checkPermission('tags_search');

        $id = $request->get('id');
        if (!$id) {
            return $this->createErrorResponse('Missing tag ID');
        }

        $type = $request->get('type');
        if (!$type) {
            return $this->createErrorResponse('Missing type');
        }

        $this->checkType($type);

        $tag = Element\Tag::getById($id);

        if (!$tag) {
            return $this->createErrorResponse([
                'msg'  => 'Tag does not exist',
                'code' => static::TAG_DOES_NOT_EXIST
            ], Response::HTTP_NOT_FOUND);
        }

        $elementsForTag = Element\Tag::getElementsForTag($tag, $type);

        $result = [];

        /** @var Element\ElementInterface $element */
        foreach ($elementsForTag as $element) {
            $item = [
                'id'   => $element->getId(),
                'type' => $element->getType()
            ];

            if (method_exists($element, 'getPublished')) {
                $item['published'] = $element->getPublished();
            }

            $result[] = $item;
        }

        return $this->createCollectionSuccessResponse($result);
    }

    /**
     * @param string $type
     *
     * @throws ResponseException
     */
    protected function checkType($type)
    {
        $validTypes = ['document', 'asset', 'object'];

        if (!in_array($type, $validTypes)) {
            throw new ResponseException($this->createErrorResponse(sprintf('Invalid type: %s', $type)));
        }

        // document -> documents
        $this->checkPermission($type . 's');
    }
}
