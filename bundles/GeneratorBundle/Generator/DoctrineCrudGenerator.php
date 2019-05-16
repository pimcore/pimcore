<?php

namespace Pimcore\Bundle\GeneratorBundle\Generator;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Generates a CRUD controller.
 *
 * The following class is copied from \Sensio\Bundle\GeneratorBundle\Generator\DoctrineCrudGenerator
 */
class DoctrineCrudGenerator extends Generator
{
    protected $filesystem;
    protected $rootDir;
    protected $routePrefix;
    protected $routeNamePrefix;
    protected $bundle;
    protected $entity;
    protected $entitySingularized;
    protected $entityPluralized;
    protected $metadata;
    protected $format;
    protected $actions;

    /**
     * @param Filesystem $filesystem
     * @param string     $rootDir
     */
    public function __construct(Filesystem $filesystem, $rootDir)
    {
        $this->filesystem = $filesystem;
        $this->rootDir = $rootDir;
    }

    /**
     * Generate the CRUD controller.
     *
     * @param BundleInterface   $bundle           A bundle object
     * @param string            $entity           The entity relative class name
     * @param ClassMetadataInfo $metadata         The entity class metadata
     * @param string            $format           The configuration format (xml, yaml, annotation)
     * @param string            $routePrefix      The route name prefix
     * @param bool              $needWriteActions Whether or not to generate write actions
     * @param bool              $forceOverwrite   Whether or not to overwrite the controller
     *
     * @throws \RuntimeException
     */
    public function generate(BundleInterface $bundle, $entity, ClassMetadataInfo $metadata, $format, $routePrefix, $needWriteActions, $forceOverwrite)
    {
        $this->routePrefix = $routePrefix;
        $this->routeNamePrefix = self::getRouteNamePrefix($routePrefix);
        $this->actions = $needWriteActions ? ['index', 'show', 'new', 'edit', 'delete'] : ['index', 'show'];

        if (count($metadata->identifier) != 1) {
            throw new \RuntimeException('The CRUD generator does not support entity classes with multiple or no primary keys.');
        }

        $this->entity = $entity;
        $entity = str_replace('\\', '/', $entity);
        $entityParts = explode('/', $entity);
        $entityName = end($entityParts);
        $this->entitySingularized = lcfirst(Inflector::singularize($entityName));
        $this->entityPluralized = lcfirst(Inflector::pluralize($entityName));
        $this->bundle = $bundle;
        $this->metadata = $metadata;
        $this->setFormat($format);

        $this->generateControllerClass($forceOverwrite);

        $dir = sprintf('%s/Resources/views/%s', $this->rootDir, strtolower($entity));

        if (!file_exists($dir)) {
            self::mkdir($dir);
        }

        $this->generateIndexView($dir);

        if (in_array('show', $this->actions)) {
            $this->generateShowView($dir);
        }

        if (in_array('new', $this->actions)) {
            $this->generateNewView($dir);
        }

        if (in_array('edit', $this->actions)) {
            $this->generateEditView($dir);
        }

        $this->generateTestClass();
        $this->generateConfiguration();
    }

    /**
     * Sets the configuration format.
     *
     * @param string $format The configuration format
     */
    protected function setFormat($format)
    {
        switch ($format) {
            case 'yml':
            case 'xml':
            case 'php':
            case 'annotation':
                $this->format = $format;
                break;
            default:
                $this->format = 'yml';
                break;
        }
    }

    /**
     * Generates the routing configuration.
     */
    protected function generateConfiguration()
    {
        if (!in_array($this->format, ['yml', 'xml', 'php'])) {
            return;
        }

        $target = sprintf(
            '%s/Resources/config/routing/%s.%s',
            $this->bundle->getPath(),
            strtolower(str_replace('\\', '_', $this->entity)),
            $this->format
        );

        $this->renderFile('crud/config/routing.'.$this->format.'.twig', $target, [
            'actions' => $this->actions,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'identifier' => $this->metadata->identifier[0],
        ]);
    }

    /**
     * Generates the controller class only.
     */
    protected function generateControllerClass($forceOverwrite)
    {
        $dir = $this->bundle->getPath();

        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $target = sprintf(
            '%s/Controller/%s/%sController.php',
            $dir,
            str_replace('\\', '/', $entityNamespace),
            $entityClass
        );

        if (!$forceOverwrite && file_exists($target)) {
            throw new \RuntimeException('Unable to generate the controller as it already exists.');
        }

        $this->renderFile('crud/controller.php.twig', $target, [
            'actions' => $this->actions,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'entity_pluralized' => $this->entityPluralized,
            'identifier' => $this->metadata->identifier[0],
            'entity_class' => $entityClass,
            'namespace' => $this->bundle->getNamespace(),
            'entity_namespace' => $entityNamespace,
            'format' => $this->format,
            // BC with Symfony 2.7
            'use_form_type_instance' => !method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix'),
        ]);
    }

    /**
     * Generates the functional test class only.
     */
    protected function generateTestClass()
    {
        $parts = explode('\\', $this->entity);
        $entityClass = array_pop($parts);
        $entityNamespace = implode('\\', $parts);

        $dir = $this->bundle->getPath().'/Tests/Controller';
        $target = $dir.'/'.str_replace('\\', '/', $entityNamespace).'/'.$entityClass.'ControllerTest.php';

        $this->renderFile('crud/tests/test.php.twig', $target, [
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'entity' => $this->entity,
            'bundle' => $this->bundle->getName(),
            'entity_class' => $entityClass,
            'namespace' => $this->bundle->getNamespace(),
            'entity_namespace' => $entityNamespace,
            'actions' => $this->actions,
            'form_type_name' => strtolower(str_replace('\\', '_', $this->bundle->getNamespace()).($parts ? '_' : '').implode('_', $parts).'_'.$entityClass),
        ]);
    }

    /**
     * Generates the index.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateIndexView($dir)
    {
        $this->renderFile('crud/views/index.html.twig.twig', $dir.'/index.html.twig', [
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_pluralized' => $this->entityPluralized,
            'entity_singularized' => $this->entitySingularized,
            'identifier' => $this->metadata->identifier[0],
            'fields' => $this->metadata->fieldMappings,
            'actions' => $this->actions,
            'record_actions' => $this->getRecordActions(),
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
        ]);
    }

    /**
     * Generates the show.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateShowView($dir)
    {
        $this->renderFile('crud/views/show.html.twig.twig', $dir.'/show.html.twig', [
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'identifier' => $this->metadata->identifier[0],
            'fields' => $this->metadata->fieldMappings,
            'actions' => $this->actions,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
        ]);
    }

    /**
     * Generates the new.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateNewView($dir)
    {
        $this->renderFile('crud/views/new.html.twig.twig', $dir.'/new.html.twig', [
            'bundle' => $this->bundle->getName(),
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'actions' => $this->actions,
            'fields' => $this->metadata->fieldMappings,
        ]);
    }

    /**
     * Generates the edit.html.twig template in the final bundle.
     *
     * @param string $dir The path to the folder that hosts templates in the bundle
     */
    protected function generateEditView($dir)
    {
        $this->renderFile('crud/views/edit.html.twig.twig', $dir.'/edit.html.twig', [
            'route_prefix' => $this->routePrefix,
            'route_name_prefix' => $this->routeNamePrefix,
            'identifier' => $this->metadata->identifier[0],
            'entity' => $this->entity,
            'entity_singularized' => $this->entitySingularized,
            'fields' => $this->metadata->fieldMappings,
            'bundle' => $this->bundle->getName(),
            'actions' => $this->actions,
        ]);
    }

    /**
     * Returns an array of record actions to generate (edit, show).
     *
     * @return array
     */
    protected function getRecordActions()
    {
        return array_filter($this->actions, function ($item) {
            return in_array($item, ['show', 'edit']);
        });
    }

    public static function getRouteNamePrefix($prefix)
    {
        $prefix = preg_replace('/{(.*?)}/', '', $prefix); // {foo}_bar -> _bar
        $prefix = str_replace('/', '_', $prefix);
        $prefix = preg_replace('/_+/', '_', $prefix);     // foo__bar -> foo_bar
        $prefix = trim($prefix, '_');

        return $prefix;
    }
}
