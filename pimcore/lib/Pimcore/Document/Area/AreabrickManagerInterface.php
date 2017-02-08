<?php
namespace Pimcore\Document\Area;

interface AreabrickManagerInterface
{
    /**
     * @param AreabrickInterface $brick
     */
    public function register(AreabrickInterface $brick);

    /**
     * @param string $id
     *
     * @return AreabrickInterface
     */
    public function get($id);
}
