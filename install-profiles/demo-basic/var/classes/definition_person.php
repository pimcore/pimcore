<?php

/**
* Generated at: 2016-08-09T09:00:04+02:00
* Inheritance: no
* Variants: no
* Changed by: system (0)
* IP: 192.168.11.111


Fields Summary:
- gender [gender]
- firstname [firstname]
- lastname [lastname]
- email [email]
- newsletterActive [newsletterActive]
- newsletterConfirmed [newsletterConfirmed]
- dateRegister [datetime]
*/


return Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'name' => 'person',
   'description' => '',
   'creationDate' => 1368620452,
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
          Pimcore\Model\DataObject\ClassDefinition\Data\Gender::__set_state(array(
             'fieldtype' => 'gender',
             'options' =>
            array (
              0 =>
              array (
                'key' => 'male',
                'value' => 'male',
              ),
              1 =>
              array (
                'key' => 'female',
                'value' => 'female',
              ),
              2 =>
              array (
                'key' => '',
                'value' => 'unknown',
              ),
            ),
             'width' => '',
             'defaultValue' => NULL,
             'queryColumnType' => 'varchar(190)',
             'columnType' => 'varchar(190)',
             'phpdocType' => 'string',
             'name' => 'gender',
             'title' => 'Gender',
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
          Pimcore\Model\DataObject\ClassDefinition\Data\Firstname::__set_state(array(
             'fieldtype' => 'firstname',
             'width' => '',
             'queryColumnType' => 'varchar',
             'columnType' => 'varchar',
             'columnLength' => 190,
             'phpdocType' => 'string',
             'regex' => '',
             'name' => 'firstname',
             'title' => 'Firstname',
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
          2 =>
          Pimcore\Model\DataObject\ClassDefinition\Data\Lastname::__set_state(array(
             'fieldtype' => 'lastname',
             'width' => '',
             'queryColumnType' => 'varchar',
             'columnType' => 'varchar',
             'columnLength' => 190,
             'phpdocType' => 'string',
             'regex' => '',
             'name' => 'lastname',
             'title' => 'Lastname',
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
          Pimcore\Model\DataObject\ClassDefinition\Data\Email::__set_state(array(
             'fieldtype' => 'email',
             'width' => '',
             'queryColumnType' => 'varchar',
             'columnType' => 'varchar',
             'columnLength' => 190,
             'phpdocType' => 'string',
             'regex' => '',
             'name' => 'email',
             'title' => 'Email',
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
          4 =>
          Pimcore\Model\DataObject\ClassDefinition\Data\NewsletterActive::__set_state(array(
             'fieldtype' => 'newsletterActive',
             'defaultValue' => 0,
             'queryColumnType' => 'tinyint(1)',
             'columnType' => 'tinyint(1)',
             'phpdocType' => 'boolean',
             'name' => 'newsletterActive',
             'title' => 'Newsletter Active',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => false,
             'index' => NULL,
             'locked' => false,
             'style' => '',
             'permissions' => NULL,
             'datatype' => 'data',
             'relationType' => false,
             'invisible' => false,
             'visibleGridView' => true,
             'visibleSearch' => true,
          )),
          5 =>
          Pimcore\Model\DataObject\ClassDefinition\Data\NewsletterConfirmed::__set_state(array(
             'fieldtype' => 'newsletterConfirmed',
             'defaultValue' => 0,
             'queryColumnType' => 'tinyint(1)',
             'columnType' => 'tinyint(1)',
             'phpdocType' => 'boolean',
             'name' => 'newsletterConfirmed',
             'title' => 'Newsletter Confirmed',
             'tooltip' => '',
             'mandatory' => false,
             'noteditable' => true,
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
          6 =>
          Pimcore\Model\DataObject\ClassDefinition\Data\Datetime::__set_state(array(
             'fieldtype' => 'datetime',
             'queryColumnType' => 'bigint(20)',
             'columnType' => 'bigint(20)',
             'phpdocType' => '\\Pimcore\\Date',
             'defaultValue' => NULL,
             'useCurrentDate' => false,
             'name' => 'dateRegister',
             'title' => 'dateRegister',
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
