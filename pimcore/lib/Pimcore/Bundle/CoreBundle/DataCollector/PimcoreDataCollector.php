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

namespace Pimcore\Bundle\CoreBundle\DataCollector;

use Pimcore\FeatureToggles\Feature;
use Pimcore\FeatureToggles\FeatureManagerInterface;
use Pimcore\FeatureToggles\Features\DebugMode;
use Pimcore\FeatureToggles\Features\DevMode;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Version;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class PimcoreDataCollector extends DataCollector
{
    /**
     * @var PimcoreContextResolver
     */
    protected $contextResolver;

    /**
     * @var FeatureManagerInterface
     */
    private $featureManager;

    public function __construct(
        PimcoreContextResolver $contextResolver,
        FeatureManagerInterface $featureManager
    ) {
        $this->contextResolver = $contextResolver;
        $this->featureManager  = $featureManager;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = [
            'version'  => Version::getVersion(),
            'revision' => Version::getRevision(),
            'context'  => $this->contextResolver->getPimcoreContext($request),
            'features' => []
        ];

        /** @var Feature $feature */
        foreach ([DebugMode::class, DevMode::class] as $feature) {
            $featureConfig = [
                'all'   => false,
                'flags' => []
            ];

            $all = $this->featureManager->isEnabled($feature::ALL());
            if ($all) {
                $featureConfig['all'] = true;
            } else {
                foreach ($feature::toArray() as $name => $flag) {
                    if (0 === $flag) {
                        continue;
                    }

                    if ($this->featureManager->isEnabled(new $feature($flag))) {
                        $featureConfig['flags'][] = $name;
                    }
                }
            }

            $this->data['features'][$feature::getType()] = $featureConfig;
        }
    }

    public function reset()
    {
        $this->data = [];
    }

    public function getName()
    {
        return 'pimcore';
    }

    /**
     * @return string|null
     */
    public function getContext()
    {
        return $this->data['context'];
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->data['version'];
    }

    /**
     * @return string
     */
    public function getRevision()
    {
        return $this->data['revision'];
    }

    public function getFeatures(): array
    {
        return $this->data['features'];
    }

    public function isFeatureAllFlagSet(string $feature): bool
    {
        return $this->data['features'][$feature]['all'];
    }

    public function featureHasFlags(string $feature): bool
    {
        return !empty($this->getFeatureFlags($feature));
    }

    public function getFeatureFlags(string $feature): array
    {
        return $this->data['features'][$feature]['flags'] ?? [];
    }
}
