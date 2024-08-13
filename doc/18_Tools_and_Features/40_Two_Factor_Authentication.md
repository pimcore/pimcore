# Two Factor Authentication

since build 256

Pimcore has an integrated two factor authentication using the Google Authenticator ([Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2), [iOS](https://itunes.apple.com/at/app/google-authenticator/id388497605))

## User Setup

By default every user can enable or disable their two factor authentication freely in the profile settings.
 ![Settings](../img/Icon_settings.png)
**(Settings -> My Profile)**

After enabling it a secret will be generated and you can setup your Google Authenticator App.

**Please be aware if you don't setup the App properly you will loose access to your account!**


 ![2fasetup](../img/two_factor_authentication_setup.png)

After reloading you will be prompt to enter the verification code for the first time.

 ![2falogin](../img/two_factor_authentication_login.png)
 
## Admin Setup

It is also possible to force users to use two factor authentication. This can be done in the Users menu by checking 'Two Factor Authentication required'.
 ![Settings](../img/Icon_settings.png)
**(Settings -> Users / Roles -> Users)**

 ![2farequired](../img/two_factor_authentication_required.png)

If this is enabled the user will have to setup two factor authentication and cannot disable it anymore.
  
  
## Config
If you want to change the default name / description that is displayed in the app you can do this by overwriting the following config:

```yaml
 scheb_two_factor:
     google:
         server_name: Pimcore                                # Server name used in QR code
         issuer: Pimcore 2 Factor Authentication             # Issuer name used in QR code
```

 ![Settings](../img/two_factor_authentication_app_descriptions.png)
 