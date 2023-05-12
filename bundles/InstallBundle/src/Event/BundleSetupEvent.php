<?php

namespace Pimcore\Bundle\InstallBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BundleSetupEvent extends Event
{
    private array $bundles;
    private array $recommendations;
    private array $required;

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

    public function addInstallableBundle(string $key, string $class, bool $recommend = false) : void
    {
        $this->bundles[$key] = $class;
        if($recommend) {
            $this->recommendations[] = $key;
        }
    }

    public function removeBundle(string $key): void
    {
        unset($this->bundles[$key]);
        if (($index = array_search($key, $this->recommendations)) !== false) {
            unset($this->recommendations[$index]);
        }
    }

    public function addRequiredBundle(string $key, string $class): void
    {
        $this->required[$key] = $class;
    }

    public function getInstallableBundles(array $bundles): array
    {
        // merge the required bundles and make sure they are unique

        return array_unique(array_merge(array_keys($this->required), $bundles));
    }

    public function getAvailableBundles(): array
    {
        return array_unique(array_merge($this->required, $this->bundles));
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
