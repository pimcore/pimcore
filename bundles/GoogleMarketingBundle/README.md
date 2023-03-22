# Google Marketing Bundle

The `Marketing Settings` gives you the possibility to configure marketing-specific settings, which are:

- Google Analytics
- Google Search Console
- Google Tag Manager


## Google Analytics

Google Analytics code is automaticaly injected during rendering the page. See [Google Analytics](./docs/05_Analytics.md) for
details.


## Google Tag Manager

The Google Tag Manager code is built and injected in a similar way as the Google Analytics one and exposes the same customization
possibilities through:

* the `GoogleTagManagerEvents::CODE_HEAD` and `GoogleTagManagerEvents::CODE_BODY` events, each defining a set of customizable
  blocks
* a dedicated template for both events which can be customized from an event listener

## Google Service Integrations
For a more detailed description see [Google Service Integration](./docs/10_Google_Services_Integration.md)