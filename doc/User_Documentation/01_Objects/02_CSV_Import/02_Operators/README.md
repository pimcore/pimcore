# Operator Overview (Experimental)

> This is an experimental feature and subject to change without notice.

## General

Change the type by either dropping a operator on a `Ignore` node or via the node's context menu.

![Setter Settings](../../../img/csvimport/change_type.png)

## Basic Settings

- `Mode`: `Default` means that the CSV data goes throw the data type's CSV processor. `Direct` sets the CSV data directly. This can be useful if the data has been processed or manipulated by another import operator already.
- `Do not overwrite`: Do not overwrite existing object data.
- `Skip empty values`: Skip empty CSV values. 

![Setter Settings](../../../img/csvimport/setter_settings.png)

Operators can then be used to change the way how the data is processed.

## Simple example

![Example](../../../img/csvimport/column_config_example.png)

## Overview 

* [Operator Ignore](./Ignore.md)
* [Operator Published](./Published.md)
* [Operator Locale Switcher](./LocaleSwitcher.md)
* [Operator Objectbrick Setter](./BrickSetter.md)
* [Operator PHPCode](./PHPCode.md)
* [Operator Iterator](./Iterator.md)
* [Operator Splitter](./Splitter.md)
* [Operator Unserialize](./Unserialize.md)
* [Operator Base64](./Base64.md)
* ...


