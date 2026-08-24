---
title: Development Details
description: Technical reference for configuration, caching, debugging, testing, and CLI in Pimcore.
---

# Development Details

This section covers the technical details relevant to developing with Pimcore. Topics include configuration
and system settings, caching strategies, debugging tools, testing approaches, and the console CLI. Use these
references alongside the core component documentation when building and maintaining Pimcore applications.

## Topics

### [Configuration](./01_Configuration/README.md)

Pimcore configuration relies on the Symfony config tree. This chapter explains system settings, Pimcore constants,
environment-specific overrides, and the storage locations where configuration files reside.

### [Database Model](./02_Database_Model.md)

Pimcore ships a set of default database tables and creates additional tables dynamically for each data object class.
This reference documents the table structure and naming conventions.

### [Cache](./03_Cache/README.md)

Pimcore uses three caching layers: the element cache for documents, assets, and data objects; the full page cache
for HTTP responses; and the runtime cache for in-request data reuse. This chapter covers configuration and
invalidation for each layer.

### [Debugging](./04_Debugging.md)

Debug mode, dev mode, server-side debugging techniques, and HTTP response headers for troubleshooting request
handling. Activate and configure these tools during development and issue analysis.

### [Extending the Pimcore User](./05_Extending_the_Pimcore_User.md)

Associate Pimcore user accounts with data objects to store additional profile data, preferences, or
application-specific attributes beyond the built-in user model.

### [Working with Sessions](./06_Working_with_Sessions/README.md)

Session handling in Pimcore builds on Symfony's session component. This chapter explains session configuration,
custom session bags, and best practices for session data access in controllers and services.

### [Preview Scheduled Content](./07_Preview_Scheduled_Content.md)

The time slider preview lets editors see how content will look at a future point in time. This feature uses the
OutputTimestampResolver to render pages as they will appear after scheduled changes take effect.

### [Testing](./08_Testing/README.md)

Application testing with PHPUnit and Codeception, running the Pimcore core test suite, and strategies for testing
services, controllers, and data object logic in isolation.

### [CLI and Pimcore Console](./09_CLI_and_Pimcore_Console.md)

Pimcore provides a console application based on the Symfony Console component. This chapter covers built-in
commands, creating custom commands, running maintenance jobs, and working in maintenance mode.
