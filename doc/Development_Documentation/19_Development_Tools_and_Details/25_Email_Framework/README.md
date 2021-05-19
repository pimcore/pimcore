# Email Framework

## General Information
The Pimcore Email Framework provides an easy way to send/create emails with Pimcore.
  
For this you have several components:
* Document\\Email
* [Pimcore\Mail](./01_Pimcore_Mail.md)

Pimcore provides a `Pimcore\Mail` Class which extends the `\Symfony\Component\Mime\Email` Class. 
If email settings are configured in your `config/config.yaml` then on initializing 
`Pimcore\Mail` object, these settings applied automatically 

Pimcore provides a `Document Email` type where you can define the recipients ... (more information 
[here](../../03_Documents/README.md)) and Twig variables. 

To send a email you just create a `Email Document` in the Pimcore Backend UI, define the subject, 
recipients, add Dynamic Placeholders... and pass this document to the `Pimcore\Mail` object. All 
nasty stuff (creating valid URLs, embedding CSS, compile Less files, rendering the document..) is 
automatically handled by the `Pimcore\Mail` object.

In the `Settings` section of the `Email Document` you can use `Full Username <user@domain.fr>` or `Full Username (user@domain.fr)` to set full username.

## Usage Example
Lets assume that we have created a `Email Document` in the Pimcore Backen UI (`/email/myemaildocument`) 
which looks like this:

![Pimcore Mail](../../img/pimcore-mail.png)

To send this document as email we just have to write the following code-snippet in our controller 
action:

```php
//dynamic parameters
$params = array('firstName' => 'Pim',
                'lastName' => 'Core',
                'product' => 73613);
 
//sending the email
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->setDocument('/email/myemaildocument');
$mail->setParams($params);
$mail->send();
```


#### Sending a Plain Text Email:
```php
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->text("This is just plain text");
$mail->send();
```

#### Sending a Rich Text (HTML) Email: 
```php
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->bcc("bcc@pimcore.org");
$mail->html("<b>some</b> rich text");
$mail->send();
```
