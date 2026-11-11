---
title: Other Bundle Events
description: Events dispatched by individual Pimcore bundles.
---

# Other Bundle Events

Individual Pimcore bundles dispatch their own events beyond those in the core framework
and Studio Backend. Each bundle defines event constants in dedicated event classes.

## Bundles Within pimcore/pimcore

These bundles ship with the `pimcore/pimcore` package but define separate event classes:

- [RedirectEvents](https://github.com/pimcore/pimcore/blob/2026.x/bundles/SeoBundle/src/Event/RedirectEvents.php)
  (SeoBundle) - redirect create, update, delete
- [JobRunStateChangedEvent](https://github.com/pimcore/pimcore/blob/2026.x/bundles/GenericExecutionEngineBundle/src/Event/JobRunStateChangedEvent.php)
  (GenericExecutionEngineBundle) - job execution state changes

## Data Hub

The Data Hub bundle defines events for configuration management and GraphQL operations:

- [ConfigEvents](https://github.com/pimcore/data-hub/blob/3.x/src/Event/ConfigEvents.php) -
  Data Hub configuration changes
- [AdminEvents](https://github.com/pimcore/data-hub/blob/3.x/src/Event/AdminEvents.php) -
  Data Hub admin operations
- GraphQL events for queries, mutations, and listings
  (see `Pimcore\Bundle\DataHubBundle\Event\GraphQL` namespace)

## Ecommerce Framework

The Ecommerce Framework provides events for checkout, order processing, and indexing:

- [CheckoutManagerEvents](https://github.com/pimcore/ecommerce-framework-bundle/blob/2.x/src/Event/CheckoutManagerEvents.php) -
  checkout process lifecycle
- [CommitOrderProcessorEvents](https://github.com/pimcore/ecommerce-framework-bundle/blob/2.x/src/Event/CommitOrderProcessorEvents.php) -
  order commit processing
- [IndexServiceEvents](https://github.com/pimcore/ecommerce-framework-bundle/blob/2.x/src/Event/IndexServiceEvents.php) -
  product index operations
- [OrderAgentEvents](https://github.com/pimcore/ecommerce-framework-bundle/blob/2.x/src/Event/OrderAgentEvents.php) -
  order agent actions
- [OrderManagerEvents](https://github.com/pimcore/ecommerce-framework-bundle/blob/2.x/src/Event/OrderManagerEvents.php) -
  order management operations

## Personalization

The Personalization bundle provides targeting and visitor profiling events:

- [TargetGroupEvents](https://github.com/pimcore/personalization-bundle/blob/1.x/src/Event/TargetGroupEvents.php) -
  target group assignment changes
- [TargetingEvents](https://github.com/pimcore/personalization-bundle/blob/1.x/src/Event/TargetingEvents.php) -
  targeting rule evaluation and visitor profiling

## Other Bundles

Additional bundles with their own events include:

- **Web-to-Print** - document rendering events
  (see `Pimcore\Bundle\WebToPrintBundle\Event\DocumentEvents`)
- **Generic Data Index** - asset, document, and data object indexing events
  (see `Pimcore\Bundle\GenericDataIndexBundle\Event` namespace)

Refer to each bundle's documentation and source code for the full list of available
events, event objects, and usage examples.
