---
title: Debugging
description: Debug mode, dev mode, server-side debugging tools, and HTTP debug headers.
---

# Debugging Pimcore

This page covers debugging tools and techniques for Pimcore development.

## Debug Mode
To enable debugging tools (profiler, toolbar, ...), Pimcore relies on the environment
variable `APP_ENV` in the `.env` file in your project root directory. Set `APP_ENV=dev` to activate it.
Additionally you can set the debug flag on the kernel by using `APP_DEBUG=1`.
For more details, see the [Symfony docs](https://symfony.com/doc/current/configuration/front_controllers_and_kernel.html#debug-mode).

## Dev Mode
The development mode enables some debugging features. This is useful if you're developing on the core of Pimcore or when 
creating a bundle. Please don't activate it in production systems!

Dev mode enables:
* Disabling of some caches
* Extensive logging to log files
* ... and some more minor behaviors

Add the following line to your `.env` file to enable dev mode.
`PIMCORE_DEV_MODE=true`

## Server-Side Debugging
Use standard PHP and Symfony debugging tools for server-side debugging:

* Reading log files as described [here](../09_Development_Tools/02_Logging/README.md)
* Using Symfony profiler console depending on the active environment. 
  Details see [Symfony docs](https://symfony.com/doc/current/reference/configuration/web_profiler.html)
* Using Xdebug and a proper IDE for stepwise debugging, see the [Xdebug docs](https://xdebug.org/)

## HTTP Headers
Pimcore might add following headers to its responses to provide additional debug information, especially concerning full
page cache: 

If response is delivered directly from full page cache: 
* `X-Pimcore-Output-Cache-Tag:output_<SOME_HASH>`: Cache tag of delivered information.  
* `X-Pimcore-Cache-Date:<CACHE_DATE>`: Date when information was stored to cache. 

If response could be delivered from full page cache, but is not: 
* `X-Pimcore-Output-Cache-Disable-Reason:<SOME REASON>`: Describes reason why response is not delivered directly from full
  page cache. Reasons can be `in debug mode`, `backend user is logged in`, `exclude path pattern in system-settings matches`,
  `exclude cookie in system-settings matches`, etc. 

One additional header to identify that response is sent by Pimcore. 
* `X-Powered-By:pimcore`: Added to every request.
