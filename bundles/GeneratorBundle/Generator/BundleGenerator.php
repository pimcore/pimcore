<?php

declare(strict_types=1);

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

namespace Pimcore\Bundle\GeneratorBundle\Generator;

use Sensio\Bundle\GeneratorBundle\Generator\BundleGenerator as BaseBundleGenerator;
use Sensio\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;
use Sensio\Bundle\GeneratorBundle\Model\Bundle;

class BundleGenerator extends BaseBundleGenerator
{
    public function generateBundle(Bundle $bundle)
    {
        parent::generateBundle($bundle);

        $dir = $bundle->getTargetDirectory();

        $parameters = [
            'namespace'       => $bundle->getNamespace(),
            'bundle'          => $bundle->getName(),
            'format'          => $bundle->getConfigurationFormat(),
            'bundle_basename' => $bundle->getBasename(),
            'extension_alias' => $bundle->getExtensionAlias(),
        ];

        $routingFilename = $bundle->getRoutingConfigurationFilename() ?: 'routing.yml';
        $routingTarget   = $dir . '/Resources/config/pimcore/' . $routingFilename;

        // create routing file
        self::mkdir(dirname($routingTarget));
        self::dump($routingTarget, '');

        $routing = new RoutingManipulator($routingTarget);
        $routing->addResource($bundle->getName(), 'annotation');

        $this->renderFile(
            'js/pimcore/startup.js.twig',
            $dir . '/Resources/public/js/pimcore/startup.js',
            $parameters
        );
    }
}
