<?php

namespace Pimcore\Bundle\PimcoreAdminBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @TODO NEEDS TO BE MIGRATED to DocumentController, after migration: DELETE!
 *
 * @Route("/autocomplete/document-mvc")
 * @Method("GET")
 */
class AutocompleteDocumentMvcController implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @Route("/bundles")
     *
     * @return JsonResponse
     */
    public function bundlesAction()
    {
        return new JsonResponse(array_keys($this->getBundles()));
    }

    /**
     * @Route("/controllers/{bundle}")
     *
     * @param null|string $bundle
     * @return JsonResponse
     */
    public function controllersAction($bundle = 'AppBundle')
    {
        $names = [];
        foreach ($this->getControllers($bundle) as $reflector) {
            $name = $reflector->getShortName();
            $name = preg_replace('/Controller$/', '', $name);

            $names[] = $name;
        }

        return new JsonResponse($names);
    }

    /**
     * @Route("/actions/{bundle}/{controller}")
     *
     * @param string $controller
     * @param null|string $bundle
     * @return JsonResponse
     */
    public function actionsAction($bundle = 'AppBundle', $controller = 'ContentController')
    {
        $names = [];
        foreach ($this->getControllerActions($bundle, $controller) as $reflector) {
            $name = $reflector->getName();
            $name = preg_replace('/Action$/', '', $name);

            $names[] = $name;
        }

        return new JsonResponse($names);
    }

    /**
     * @return array
     */
    protected function getBundles()
    {
        return $this->container->getParameter('kernel.bundles');
    }

    /**
     * @param string $bundle
     * @return \ReflectionClass
     */
    protected function getBundleReflector($bundle)
    {
        $bundles = $this->getBundles();
        if (!isset($bundles[$bundle])) {
            throw new NotFoundHttpException();
        }

        $bundleClass = $bundles[$bundle];
        $reflector = new \ReflectionClass($bundleClass);

        return $reflector;
    }

    /**
     * @param string $bundle
     * @return \ReflectionClass[]
     */
    protected function getControllers($bundle)
    {
        $reflector = $this->getBundleReflector($bundle);
        $controllers = [];

        $controllerDirectory = dirname($reflector->getFileName()) . '/Controller';
        if (file_exists($controllerDirectory)) {
            $finder = new Finder();
            $finder
                ->files()
                ->name('*Controller.php')
                ->in($controllerDirectory);

            foreach ($finder as $controllerFile) {
                $className = $controllerFile->getBasename('.php');
                $fullClassName = $reflector->getNamespaceName() . '\\Controller\\' . $className;

                if (class_exists($fullClassName)) {
                    $controllerReflector = new \ReflectionClass($fullClassName);

                    if ($controllerReflector->isInstantiable()) {
                        $controllers[$controllerReflector->getShortName()] = $controllerReflector;
                    }
                }
            }
        }

        return $controllers;
    }

    /**
     * @param string $bundle
     * @param string $controller
     * @return \ReflectionMethod[]
     */
    protected function getControllerActions($bundle, $controller)
    {
        $controllers = $this->getControllers($bundle);
        if (!isset($controllers[$controller])) {
            throw new NotFoundHttpException();
        }

        /** @var \ReflectionClass $controllerReflector */
        $controllerReflector = $controllers[$controller];

        $methods = [];
        foreach ($controllerReflector->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC) as $method) {
            if (preg_match('/^(.*)Action$/', $method->getName())) {
                $methods[] = $method;
            }
        }

        return $methods;
    }
}
