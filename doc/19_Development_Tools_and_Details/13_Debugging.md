# Debugging Pimcore

In this chapter, a few insights, tips and tricks for debugging Pimcore are shown. This should give you a
head start when developing with Pimcore. 

## Debug Mode
In order to include some specific debugging tools (profiler, toolbar, ...), Pimcore relies on environment 
variable `APP_ENV` in `.env` file in your project root directory. `APP_ENV=dev`
Additionally you can set the debug flag on the kernel by using `APP_DEBUG=1`. 
More details on that see [Symfony docs](https://symfony.com/doc/current/configuration/front_controllers_and_kernel.html#debug-mode)

## Dev Mode
The development mode enables some debugging features. This is useful if you're developing on the core of Pimcore or when 
creating a bundle. Please don't activate it in production systems!

What exactly does the dev mode:
* Loading the source javascript files (uncompressed & commented)
* Disables some caches (Cache, ...)
* extensive logging into log files
* ... and some more little things

Add the following line to your `.env` file to enable dev mode.
`PIMCORE_DEV_MODE=true`

## Server-Side Debugging
For server side debugging, standard php and Symfony framework debugging tools like the following can be used.

* Reading log files as described [here](07_Logging.md)
* Using Symfony profiler console depending on the active environment. 
  Details see [Symfony docs](https://symfony.com/doc/current/reference/configuration/web_profiler.html)
* Using Xdebug and a proper IDE for stepwise debugging, more information see [Xdebug docs](https://xdebug.org/)


## Client-Side Debugging
For proper debugging of Pimcore backend UI activate the DEV-Mode in [system settings](../18_Tools_and_Features/25_System_Settings.md).
By doing so, all javascript files are delivered uncompressed and commented. Thus debugging tools provided by browsers 
(like actual error lines, debugger, stack trace, etc.) can be used.


## HTTP Headers
Pimcore might add following headers to its responses to provide additional debug information, especially concerning full
page cache: 

If response is delivered directly from full page cache: 
* `X-Pimcore-Output-Cache-Tag:output_<SOME_HASH>`: Cache tag of delivered information.  
* `X-Pimcore-Cache-Date:<CACHE_DATE>`: Date when information was stored to cache. 

If response could be delivered from full page cache, but is not: 
* `X-Pimcore-Output-Cache-Disable-Reason:<SOME REASON>`: Describes reason why response is not delivered directly from full
  page cache. Reasons can be `in debug mode`, `backend user is logged in`, `exclude path patter in system-settings matches`, 
  `exclude cookie in system-setings matches`, etc. 

One additional header to identify that response is sent by Pimcore. 
* `X-Powered-By:pimcore`: Added to every request.
