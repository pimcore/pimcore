# Pimcore Mail

The `Pimcore\Mail` Class extends the [`\Swift_Message`](http://swiftmailer.org/docs/introduction.html) 
Class and adds some features for the usage with Pimcore.

When you create a new `Pimcore\Mail` instance the E-Mail settings from *Settings* > *System* > *Email Settings*
are automatically applied.

If the Debug Mode in *Settings* > *System* > *Debug* is enabled, all emails will be sent to the 
Debug Email recipients defined in *Settings* > *System* > *Email Settings* > *Debug Email Addresses*. 
Additionally the debug information (to whom the email would have been sent) is appended to the email 
and the Subject contains the prefix "Debug email:".
This is done via an extension of the swift mailer `RedirectingPlugin`.   

The `Pimcore\Mail` Class automatically takes care of the nasty stuff (embedding CSS, compiling Less 
files, normalizing URLs, replacement of Dynamic Placeholders...). Note that all CSS files are embedded 
to the html with a `<style>` tag because the image paths are also normalised.
 
Optionally, you can use `html2text` from [Martin Bayer](http://www.mbayer.de/html2text/index.shtml) 
for the generation of the text version by calling `enableHtml2textBinary()`.
 
On Debian/Ubuntu you can install it with: `apt-get install html2text`


## Useful Methods

| Method | Description |
| ------ | ----------- |
| disableLogging() | Disables email logging - by default it is enabled |
| setParams(array) | Sets the parameters for the email view and the Placeholders |
| setParam($key, $value) | Sets a single parameter for the email view and the Placeholders |
| isValidEmailAddress(emailAddress) | Static helper to validate a email address |
| setDocument(Document_Email) | Sets the email document |
| getDocument() | Returns the Document |
| getSubjectRendered() | Replaces the placeholders with the content and returns the rendered Subject |
| getBodyHtmlRendered() | Replaces the placeholders with the content and returns the rendered Html |
| getBodyTextRendered() | Replaces the placeholders with the content and returns the rendered text if a text was set with `$mail->setBodyText()`. If no text was set, a text version on the html email will be automatically created |
| enableHtml2textBinary() | `html2text` from Martin Bayer (http://www.mbayer.de/html2text/index.shtml) - throws an Exception if html2text is not installed! |
| setHtml2TextOptions($options) | set options for html2text (only for binary version) |


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
$mail->setBodyText("This is just plain text");
$mail->send();
 
// Sending a rich text (HTML) email:&nbsp;
 
 
$mail = new \Pimcore\Mail();
$mail->addTo('example@pimcore.org');
$mail->addBcc("bcc@pimcore.org");
$mail->setBodyHtml("<b>some</b> rich text");
$mail->send();
 
//adding an asset as attachment
if($asset instanceof Asset) {
   $mail->createAttachment($asset->getData(), $asset->getMimetype(), $asset->getFilename());
}
```
