<?php

/**
* Generated at: 2016-08-09T09:00:04+02:00
* Inheritance: no
* Variants: no
* Changed by: system (0)
* IP: 192.168.11.111


Fields Summary:
- date [datetime]
- person [href]
- message [textarea]
- terms [checkbox]
*/


return Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'name' => 'inquiry',
   'description' => '',
   'creationDate' => 1368620413,
   'modificationDate' => 1470726004,
   'userOwner' => 0,
   'userModification' => 0,
   'parentClass' => '',
   'useTraits' => NULL,
   'allowInherit' => false,
   'allowVariants' => false,
   'showVariants' => false,
   'layoutDefinitions' =>
  Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
     'fieldtype' => 'panel',
     'labelWidth' => 100,
     'layout' => NULL,
     'name' => 'pimcore_root',
     'type' => NULL,
     'region' => NULL,
     'title' => NULL,
     'width' => NULL,
     'height' => NULL,
     'collapsible' => false,
     'collapsed' => NULL,
     'bodyStyle' => NULL,
     'datatype' => 'layout',
     'permissions' => NULL,
     'childs' =>
    array (
      0 =>
      Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
         'fieldtype' => 'panel',
         'labelWidth' => 100,
         'layout' => '',
         'name' => 'Layout',
         'type' => NULL,
         'region' => '',
         'title' => '',
         'width' => '',
         'height' => '',
         'collapsible' => false,
         'collapsed' => false,
         'bodyStyle' => '',
         'datatype' => 'layout',
         'permissions' => NULL,
         'childs' =>
        array (
          0 =>
          Pimcore\Model\DataObject\ClassDefinition\Layout\Panel::__set_state(array(
             'fieldtype' => 'panel',
             'labelWidth' => 100,
             'layout' => NULL,
             'name' => 'Layout',
             'type' => NULL,
             'region' => NULL,
             'title' => NULL,
             'width' => NULL,
             'height' => NULL,
             'collapsible' => false,
             'collapsed' => NULL,
             'bodyStyle' => NULL,
             'datatype' => 'layout',
             'permissions' => NULL,
             'childs' =>
            array (
              0 =>
              Pimcore\Model\DataObject\ClassDefinition\Data\Datetime::__set_state(array(
                 'fieldtype' => 'datetime',
                 'queryColumnType' => 'bigint(20)',
                 'columnType' => 'bigint(20)',
                 'phpdocType' => '\\Pimcore\\Date',
                 'defaultValue' => NULL,
                 'useCurrentDate' => false,
                 'name' => 'date',
                 'title' => 'Date',
                 'tooltip' => '',
                 'mandatory' => false,
                 'noteditable' => false,
                 'index' => false,
                 'locked' => false,
                 'style' => '',
                 'permissions' => NULL,
                 'datatype' => 'data',
                 'relationType' => false,
                 'invisible' => false,
                 'visibleGridView' => false,
                 'visibleSearch' => false,
              )),
              1 =>
              Pimcore\Model\DataObject\ClassDefinition\Data\Href::__set_state(array(
                 'fieldtype' => 'href',
                 'width' => 450,
                 'assetUploadPath' => '',
                 'relationType' => true,
                 'queryColumnType' =>
                array (
                  'id' => 'int(11)',
                  'type' => 'enum(\'document\',\'asset\',\'object\')',
                ),
                 'phpdocType' => '\\Pimcore\\Model\\Document\\Page | \\Pimcore\\Model\\Document\\Snippet | \\Pimcore\\Model\\Document | \\Pimcore\\Model\\Asset | \\Pimcore\\Model\\DataObject\\AbstractObject',
                 'objectsAllowed' => true,
                 'assetsAllowed' => false,
                 'assetTypes' =>
                array (
                ),
                 'documentsAllowed' => false,
                 'documentTypes' =>
                array (
                ),
                 'lazyLoading' => false,
                 'classes' =>
                array (
                  0 =>
                  array (
                    'classes' => 'person',
                  ),
                ),
                 'name' => 'person',
                 'title' => 'Person',
                 'tooltip' => '',
                 'mandatory' => true,
                 'noteditable' => false,
                 'index' => false,
                 'locked' => false,
                 'style' => '',
                 'permissions' => NULL,
                 'datatype' => 'data',
                 'columnType' => NULL,
                 'invisible' => false,
                 'visibleGridView' => false,
                 'visibleSearch' => false,
              )),
              2 =>
              Pimcore\Model\DataObject\ClassDefinition\Data\Textarea::__set_state(array(
                 'fieldtype' => 'textarea',
                 'width' => 450,
                 'height' => '',
                 'queryColumnType' => 'longtext',
                 'columnType' => 'longtext',
                 'phpdocType' => 'string',
                 'name' => 'message',
                 'title' => 'Message',
                 'tooltip' => '',
                 'mandatory' => false,
                 'noteditable' => false,
                 'index' => false,
                 'locked' => false,
                 'style' => '',
                 'permissions' => NULL,
                 'datatype' => 'data',
                 'relationType' => false,
                 'invisible' => false,
                 'visibleGridView' => false,
                 'visibleSearch' => false,
              )),
              3 =>
              Pimcore\Model\DataObject\ClassDefinition\Data\Checkbox::__set_state(array(
                 'fieldtype' => 'checkbox',
                 'defaultValue' => 0,
                 'queryColumnType' => 'tinyint(1)',
                 'columnType' => 'tinyint(1)',
                 'phpdocType' => 'boolean',
                 'name' => 'terms',
                 'title' => 'Terms of Use',
                 'tooltip' => '',
                 'mandatory' => false,
                 'noteditable' => false,
                 'index' => false,
                 'locked' => false,
                 'style' => '',
                 'permissions' => NULL,
                 'datatype' => 'data',
                 'relationType' => false,
                 'invisible' => false,
                 'visibleGridView' => false,
                 'visibleSearch' => false,
              )),
            ),
             'locked' => false,
          )),
        ),
         'locked' => false,
      )),
    ),
     'locked' => false,
  )),
   'icon' => '',
   'previewUrl' => '',
   'group' => NULL,
   'propertyVisibility' =>
  array (
    'grid' =>
    array (
      'id' => true,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
    'search' =>
    array (
      'id' => true,
      'path' => true,
      'published' => true,
      'modificationDate' => true,
      'creationDate' => true,
    ),
  ),
   'dao' => NULL,
));
