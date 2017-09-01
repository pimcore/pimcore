<?php

/**
* Generated at: 2017-04-10T12:45:18+02:00
* Inheritance: no
* Variants: no
* Changed by: admin (3)
* IP: 192.168.85.1


Fields Summary:
- username [input]
- password [password]
- roles [multiselect]
*/


return Pimcore\Model\DataObject\ClassDefinition::__set_state(array(
   'name' => 'user',
   'description' => '',
   'creationDate' => 0,
   'modificationDate' => 1491821118,
   'userOwner' => 3,
   'userModification' => 3,
   'parentClass' => '',
   'useTraits' => '',
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
     'collapsible' => NULL,
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
         'width' => NULL,
         'height' => NULL,
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
             'title' => '',
             'width' => NULL,
             'height' => NULL,
             'collapsible' => false,
             'collapsed' => false,
             'bodyStyle' => '',
             'datatype' => 'layout',
             'permissions' => NULL,
             'childs' =>
            array (
              0 =>
              Pimcore\Model\DataObject\ClassDefinition\Data\Input::__set_state(array(
                 'fieldtype' => 'input',
                 'width' => NULL,
                 'queryColumnType' => 'varchar',
                 'columnType' => 'varchar',
                 'columnLength' => 190,
                 'phpdocType' => 'string',
                 'regex' => '',
                 'name' => 'username',
                 'title' => 'Username',
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
              Pimcore\Model\DataObject\ClassDefinition\Data\Password::__set_state(array(
                 'fieldtype' => 'password',
                 'width' => '',
                 'queryColumnType' => 'varchar(190)',
                 'columnType' => 'varchar(190)',
                 'phpdocType' => 'string',
                 'algorithm' => 'password_hash',
                 'salt' => '',
                 'saltlocation' => 'back',
                 'name' => 'password',
                 'title' => 'Password',
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
              Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect::__set_state(array(
                 'fieldtype' => 'multiselect',
                 'options' =>
                array (
                  0 =>
                  array (
                    'key' => 'Admin',
                    'value' => 'ROLE_ADMIN',
                  ),
                  1 =>
                  array (
                    'key' => 'User',
                    'value' => 'ROLE_USER',
                  ),
                ),
                 'width' => '',
                 'height' => '',
                 'maxItems' => '',
                 'queryColumnType' => 'text',
                 'columnType' => 'text',
                 'phpdocType' => 'array',
                 'name' => 'roles',
                 'title' => 'Roles',
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
     'locked' => NULL,
  )),
   'icon' => '',
   'previewUrl' => '',
   'group' => '',
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
