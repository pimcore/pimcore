# Pimcore Mail

The `Pimcore\Mail` Class extends the [`Symfony\Component\Mime\Email`](https://symfony.com/doc/current/mailer.html#email-addresses) 
Class and adds some features for the usage with Pimcore.

When you create a new `Pimcore\Mail` instance the E-Mail settings from *Settings* > *System* > *Email Settings*
are automatically applied.

If the Debug Mode is enabled, all emails will be sent to the 
Debug Email recipients defined in *Settings* > *System* > *Debug* > *Debug Email Addresses*. 
Additionally the debug information (to whom the email would have been sent) is appended to the email 
and the Subject contains the prefix "Debug email:".

This is done by extending Symfony Mailer, with injected service `RedirectingPlugin`, which calls beforeSendPerformed before mail is sent and sendPerformed immediately after email is sent.

Emails are sent via transport and `\Pimcore\Mailer` requires transports: `main` for sending emails and  `pimcore_newsletter` for sending newsletters(if newsletter specific settings are used), which needs to be configured in your config.yml e.g.,
```yaml
framework:
    mailer:
        transports:
            main: smtp://user:pass@smtp.example.com:port
            pimcore_newsletter: smtp://user:pass@smtp.example.com:port
```
Please refer to the [Transport Setup](https://symfony.com/doc/current/mailer.html#transport-setup) for further details on how this can be set up.


The `Pimcore\Mail` Class automatically takes care of the nasty stuff (embedding CSS, 
normalizing URLs and Twig expressions ...). Note that all CSS files are embedded 
to the html with a `<style>` tag because the image paths are also normalised.

## Useful Methods

| Method                            | Description                                                                                                                                                                                                |
|-----------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| disableLogging()                  | Disables email logging - by default it is enabled                                                                                                                                                          |
| setParams(array)                  | Sets the parameters to the request object and the Twig engine                                                                                                                                             |
| setParam($key, $value)            | Sets a single parameter to the request object and the Twig engine                                                                                                                                         |
| isValidEmailAddress(emailAddress) | Static helper to validate a email address                                                                                                                                                                  |
| setDocument(Document_Email)       | Sets the email document                                                                                                                                                                                    |
| getDocument()                     | Returns the Document                                                                                                                                                                                       |
| getSubjectRendered()              | Renders the content as a Twig template with the provided params and returns the resulting Subject                                                                                                                                |
| getBodyHtmlRendered()             | Renders the content as a Twig template with the content and returns the resulting HTML                                                                                                                                   |
| getBodyTextRendered()             | Renders the content as a Twig template with the content and returns the resulting text if a text was set with `$mail->setBodyText()`. If no text was set, a text version on the html email will be automatically created |
| setHtml2TextOptions($options)     | set options for html2text (only for binary version)                                                                                                                                                        |


## Usage Example

```php
$params = ['firstName' => 'Pim', 'lastName' => 'Core', 'product' => 73613];
 
//sending an email document (pimcore document)
$mail = new \Pimcore\Mail();
$mail->addTo('example@pimcore.org');
$mail->setDocument('/email/myemaildocument');
$mail->setParams($params);
$mail->send();
 
 
// sending a text-mail
 
$mail = new \Pimcore\Mail();
$mail->addTo('example@pimcore.org');
$mail->text("This is just plain text");
$mail->send();
 
// Sending a rich text (HTML) email with Twig expressions 
$mail = new \Pimcore\Mail();
$mail->addTo('example@pimcore.org');
$mail->addBcc("bcc@pimcore.org");
$mail->setParams([
    'myParam' => 'Just a simple text'
]);
$mail->html("<b>some</b> rich text: {{ myParam }}");
$mail->send();
 
//adding an asset as attachment
if($asset instanceof Asset) {
   $mail->createAttachment($asset->getData(), $asset->getMimetype(), $asset->getFilename());
}

//Embedding Images
$mail = new \Pimcore\Mail();
$mail->addTo('example@pimcore.org');

$mail->embed($asset->getData(), 'logo', $asset->getMimetype());
//or
$mail->embedFromPath($asset->getFileSystemPath(), 'logo', $asset->getMimetype());

$mail->html("Embedded Image: <img src='cid:logo'>"); //image name(passed second argument in embed) as ref
$mail->send();
```
