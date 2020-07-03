# Marketing Settings

The `Marketing Settings` give you the possibility to configure marketing-specific settings, which are:

- [Google Analytics](./05_Analytics.md)
- Google Search Console
- Google Tag Manager
- [Matomo](./07_Piwik.md)


## Google Analytics

Google Analytics code is automaticaly injected during rendering the page. See [Google Analytics](./05_Analytics.md) for
details.


## Google Tag Manager

The Google Tag Manager code is built and injected in a similar way as the Google Analytics one and exposes the same customization
possibilities through:

* the `GoogleTagManagerEvents::CODE_HEAD` and `GoogleTagManagerEvents::CODE_BODY` events, each defining a set of customizable
  blocks
* a dedicated template for both events which can be customized from an event listener


## Matomo

Similar to Google Analytics, a Matomo tracking code can be automatically injected into each response. See [Matomo](./07_Piwik.md)
for details.
