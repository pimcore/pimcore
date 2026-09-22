---
title: Studio Backend Events
description: Overview of Studio Backend API events and where to find the full reference.
---

# Studio Backend Events

The Studio Backend Bundle dispatches events that allow customization of API responses
and element resolution behavior. These events use the Symfony EventDispatcher and
follow the `EventSubscriberInterface` pattern.

The two main event categories are:

- **PreResponse events** - fired before the API returns a response for an element
  (asset, data object, document). Use these to add custom attributes, modify response
  data, or inject additional information into the API output.
- **ElementResolveEvent** - fired during element resolution to customize how elements
  are looked up by ID or search term.

Key event classes:

- `Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse\AssetEvent` -
  fired before asset API responses
- `Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse\DataObjectEvent` -
  fired before data object API responses
- `Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\DocumentEvent` -
  fired before document API responses
- `Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResolve\ElementResolveEvent` -
  fired during element resolution

## Full Reference

The canonical documentation for Studio Backend events - including the complete event list,
all available event classes, and detailed code examples - lives in the Studio Backend Bundle:

- [Additional and Custom Attributes](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/02_Additional_and_Custom_Attributes.md) -
  enrich API responses with custom data via PreResponse events
- [Extending via Events](https://github.com/pimcore/studio-backend-bundle/blob/1.x/doc/03_Extending/01_Extending_via_Events.md) -
  full event reference with examples for element resolution and response customization
