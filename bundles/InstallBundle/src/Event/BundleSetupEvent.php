<?php

namespace Pimcore\Bundle\InstallBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BundleSetupEvent extends Event
{
    private array $bundles;
    private array $recommendations;

    public function __construct(array $bundles, array $recommendations)
    {
       $this->bundles = $bundles;
       $this->recommendations = $recommendations;
    }

    public function getBundles(): array
    {
        return $this->bundles;
    }

    public function getRecommendedBundles(): array
    {
        return $this->recommendations;
    }

    public function addBundle(string $key, string $class) : void
    {
        $this->bundles[$key] = $class;
    }

    public function removeBundle(string $key): void
    {
        unset($this->bundles[$key]);
    }

    public function addRecommendation(string $recommendedBundleKey): void
    {
        // Before adding a recommendation check if the bundle is available in the bundles array
        if(array_key_exists($recommendedBundleKey, $this->bundles)) {
            $this->recommendations[] = $recommendedBundleKey;
        }
    }

    public function removeRecommendation(string $recommendedBundleKey): void
    {
        // Removing recommendations is no problem
        if (($key = array_search($recommendedBundleKey, $this->recommendations)) !== false) {
            unset($this->recommendations[$key]);
        }
    }

    /**
     * Used for the demos e.g. to skip bundle installation question
     * You can also use it to build your own bundle list and recommendations
     */
    public function clearBundlesAndRecommendations(): void
    {
        $this->bundles = [];
        $this->recommendations = [];
    }
}
