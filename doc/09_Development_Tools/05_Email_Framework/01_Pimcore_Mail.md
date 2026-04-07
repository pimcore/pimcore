---
title: Pimcore Mail
description: Pimcore\Mail class methods and usage examples.
---

# Pimcore Mail

The `Pimcore\Mail` class extends [`Symfony\Component\Mime\Email`](https://symfony.com/doc/current/mailer.html#email-addresses)
and adds Pimcore-specific features.

When email settings are configured in `config/config.yaml`, initializing a `Pimcore\Mail`
object applies them automatically. Configure [email settings](./README.md#general-information)
before using `Pimcore\Mail`.

The `Pimcore\Mail` class automatically handles CSS embedding,
URL normalization, and Twig expression processing. All CSS files are embedded
into the HTML with a `<style>` tag because image paths are also normalized.

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
| getBodyTextRendered()             | Renders the content as a Twig template with the content and returns the resulting text if a text was set with `$mail->text()`. If no text was set, a text version on the html email will be automatically created |

## Usage Example

```php
$params = ['firstName' => 'Pim', 'lastName' => 'Core', 'product' => 73613];

//sending an email document (pimcore document)
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->setDocument('/email/myemaildocument');
$mail->setParams($params);
$mail->send();


// sending a text-mail

$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->text("This is just plain text");
$mail->send();

// Sending a rich text (HTML) email with Twig expressions
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->bcc("bcc@pimcore.org");
$mail->setParams([
    'myParam' => 'Just a simple text'
]);
$mail->html("<b>some</b> rich text: {{ myParam }}");
$mail->send();

//adding an asset as attachment
if($asset instanceof Asset) {
   $mail->attach($asset->getData(), $asset->getFilename(), $asset->getMimeType());
}

//Embedding Images
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');

$mail->embed($asset->getData(), 'logo', $asset->getMimeType());
//or
$mail->embedFromPath($asset->getRealFullPath(), 'logo', $asset->getMimeType());

$mail->html("Embedded Image: <img src='cid:logo'>"); //image name(passed second argument in embed) as ref
$mail->send();
```
