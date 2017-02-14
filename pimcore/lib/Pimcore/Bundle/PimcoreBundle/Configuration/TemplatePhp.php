<?php

namespace Pimcore\Bundle\PimcoreBundle\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template as BaseTemplate;

/**
 * Same annotation as Template, but defaults to the php engine
 *
 * @Annotation
 */
class TemplatePhp extends BaseTemplate
{
    /**
     * The template engine used when a specific template isn't specified.
     *
     * @var string
     */
    protected $engine = 'php';
}
