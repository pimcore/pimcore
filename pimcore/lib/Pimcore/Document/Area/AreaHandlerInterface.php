<?php
namespace Pimcore\Document\Area;

use Pimcore\Model\Document\Tag;
use Pimcore\Model\Document\Tag\Area\Info;

interface AreaHandlerInterface
{
    /**
     * Determine if handler strategy supports the tag
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @return bool
     */
    public function supports(Tag $tag);

    /**
     * Build tag options
     *
     * @param Tag|Tag\Area|Tag\Areablock $tag
     * @param array $options
     *
     * @return array
     */
    public function buildOptions(Tag $tag, array $options);

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderFrontend(Info $info, array $params);
}
