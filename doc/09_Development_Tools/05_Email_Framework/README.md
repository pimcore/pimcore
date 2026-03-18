---
title: Email Framework
description: Send emails with Pimcore\Mail, document email templates, and debug mode.
---

# Email Framework

## General Information

The Pimcore Email Framework handles email creation and delivery.

Components:
* Document\\Email
* [Pimcore\Mail](./01_Pimcore_Mail.md)

Pimcore provides a `Pimcore\Mail` class extending `\Symfony\Component\Mime\Email`.
When email settings are configured in `config/config.yaml`, initializing a `Pimcore\Mail`
object applies them automatically.

Configure email settings in `config/config.yaml`:
```yaml
pimcore:
    email:
        sender:
            name: 'Pimcore Demo'
            email: demo@pimcore.com
        return:
            name: ''
            email: ''
```
Configure debug email addresses in Pimcore Studio under system settings.

When Debug Mode is enabled, all emails are sent to the debug email recipients
defined in the system settings.
The debug information (original recipients) is appended to the email,
and the subject receives the prefix "Debug email:".

This works by extending Symfony Mailer with an injected `RedirectingPlugin` service,
which calls `beforeSendPerformed` before sending and `sendPerformed` immediately after.

Emails require configured transports: `main` for sending emails and `pimcore_newsletter`
for newsletters (when PimcoreNewsletterBundle is enabled and installed).

```yaml
framework:
    mailer:
        transports:
            main: smtp://user:pass@smtp.example.com:port
            pimcore_newsletter: smtp://user:pass@smtp.example.com:port
```
Refer to the [Transport Setup](https://symfony.com/doc/current/mailer.html#transport-setup)
for further details.


Pimcore provides a `Document Email` type for defining recipients, Twig variables, and more
(see [Documents](../../01_Documents/README.md)).

To send an email, create an `Email Document` in Pimcore Studio, define the subject,
recipients, and dynamic placeholders, then pass the document to `Pimcore\Mail`.
URL generation, CSS embedding, Less compilation, and document rendering are handled automatically.

In the `Settings` section of the `Email Document`, use `Full Username <user@domain.fr>`
or `Full Username (user@domain.fr)` to set the full username.

## Usage Example

Given an `Email Document` at `/email/myemaildocument` in Pimcore Studio:

![Pimcore Mail](../../img/pimcore-mail.png)

Send it from a controller action:

```php
//dynamic parameters
$params = array('firstName' => 'Pim',
                'lastName' => 'Core',
                'product' => \Pimcore\Model\DataObject::getById(73613)
                );

//sending the email
$mail = new \Pimcore\Mail();
$mail->to('example@pimcore.org');
$mail->setDocument('/email/myemaildocument');
$mail->setParams($params);
$mail->send();
```

Access the parameters in the mail content:
```twig
Hello {{ firstName }} {{ lastName }}
Regarding the product {{ product.getName() }} ....
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

## Sandbox Restrictions

Sending mails renders user-controlled Twig templates in a sandbox
with restrictive security policies for tags, filters, and functions.
Allow additional capabilities via configuration:

```yaml
    pimcore:
          templating_engine:
              twig:
                sandbox_security_policy:
                  tags: ['if']
                  filters: ['upper']
                  functions: ['include', 'path']
```
