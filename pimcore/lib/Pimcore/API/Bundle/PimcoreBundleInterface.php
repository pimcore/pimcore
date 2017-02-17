<?php
namespace Pimcore\API\Bundle;

use Pimcore\API\Bundle\Installer\InstallerInterface;
use Pimcore\Bundle\PimcoreBundle\Routing\RouteReferenceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

interface PimcoreBundleInterface
{
    /**
     * If the plugin has an installation routine, an installer is responsible of handling installation related tasks
     *
     * @param ContainerInterface $container
     * @return InstallerInterface|null
     */
    public function getInstaller(ContainerInterface $container);

    /**
     * Get path to include in admn iframe
     *
     * @return string|RouteReferenceInterface|null
     */
    public function getAdminIframePath();

    /**
     * Get javascripts to include in admin interface
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getJsPaths();

    /**
     * Get stylesheets to include in admin interface
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getCssPaths();

    /**
     * Get javascripts to include in editmode
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeJsPaths();

    /**
     * Get stylesheets to include in editmode
     *
     * Strings will be directly included, RouteReferenceInterface objects are used to generate an URL through the
     * router.
     *
     * @return string[]|RouteReferenceInterface[]
     */
    public function getEditmodeCssPaths();
}
