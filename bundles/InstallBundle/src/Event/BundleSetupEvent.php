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

    public function addRecommendation(string $recommendedBundleKey): void
    {
        $this->recommendations[] = $recommendedBundleKey;
    }

    public function removeBundle(string $key): void
    {
        if(array_key_exists($key, $this->bundles)) {
            unset($this->bundles[$key]);
        }
    }

    /**
     * Used for the demos, not bundles should be available here
     */
    public function clearBundlesAndRecommendations(): void
    {
        $this->bundles = [];
        $this->recommendations = [];
    }
}
