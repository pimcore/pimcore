<?php
namespace Pimcore\Document\Area;

use Pimcore\Model\Document\Tag\Area\Info;

interface AreaHandlerStrategyInterface
{
    /**
     * Determine if handler strategy supports the tag
     *
     * @param Info $info
     * @return bool
     */
    public function supports(Info $info);

    /**
     * Render the area frontend
     *
     * @param Info $info
     * @param array $params
     */
    public function renderFrontend(Info $info, array $params);
}
