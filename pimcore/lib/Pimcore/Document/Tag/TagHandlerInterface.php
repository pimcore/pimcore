<?php
namespace Pimcore\Document\Tag;

use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;

interface TagHandlerInterface
{
    /**
     * Determine if handler strategy supports the tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return bool
     */
    public function supports(Tag $tag);

    /**
     * Get available areas for an areablock
     *
     * @param Tag\Areablock $tag
     * @param array $options
     *
     * @return array
     */
    public function getAvailableAreablockAreas(Tag\Areablock $tag, array $options);

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderAreaFrontend(Info $info, array $params);
}
