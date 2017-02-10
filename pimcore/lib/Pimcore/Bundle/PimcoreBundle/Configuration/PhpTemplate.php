<?php

namespace Pimcore\Bundle\PimcoreBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template as BaseTemplate;

/**
 * @Annotation
 */
class PhpTemplate extends BaseTemplate
{
    /**
     * The template engine used when a specific template isn't specified.
     *
     * @var string
     */
    protected $engine = 'php';
}
