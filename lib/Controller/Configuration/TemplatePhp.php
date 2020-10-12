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

namespace Pimcore\Controller\Configuration;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template as BaseTemplate;

/**
 * Same annotation as Template, but defaults to the php engine
 *
 * @Annotation
 *
 * @deprecated
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
