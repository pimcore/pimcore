# Newsletter

## General

Pimcore provides a basic newsletter framework. 

The main advantage is that you can send completely customized / personalized newsletters by using all the data stored 
in the system (products, ...).
The content of the e-mail is rendered individually for every recipient (the user object is available in the action and view), 
this gives you the absolute freedom for your content.

The newsletter framework is just a wrapper for existing functionality in Pimcore. 
This makes it easy to use and gives you all the advantages Pimcore offers you. 

The newsletter content is assembled in an *Newsletter* document.
Therefore, your newsletter template is just a simple action and view. In the view you can use all features known 
from the other document types (page, snippets...).

As mentioned before, this document is rendered individually for every user, that makes it possible to include content 
depending on the user data.

If your *mailing list* is stored in objects, you can find few special data components for that case.  

## Basic Setup

**Class definition**

The class definition below shows how to build a class used by the newsletter list.
 
![Class definition for the newsletter](../img/newsletter_class_definition.png)

The purpose of the fields gender, *firstname*, *lastname* and *email* should be clear.
*newsletterActive* and *newsletterConfirmed* are used to save the state of the user (also used by the frontend framework). 
The *newsletterActive* data component tells the newsletter framework whether to send the newsletter to this user or not. 
The *newsletterConfirmed* component is used for double opt-in (provided by the frontend framework). 

**Only if newsletterActive and newsletterConfirmed are ticked the user in the object receives the newsletter.**

The *newsletterConfirmed* cannot be set in the admin interface, the reason is simple: the frontend framework logs every 
activity to *Notes & Events* including the IP etc. from the user, so it's possible to track every modification the user 
has made to his profile. 

Now if the editor is able to change this important setting the audit trail is pitted and it's not clear why the user 
receives the newsletter. 

Of course it's possible to change the value via API (eg. in importers).

### URL prefix for static assets

You are able to configure the default URL prefix in order to make sure, that your static assets
(e.g. images or CSS files) are getting prefixed correctly.

```yml
pimcore:
    documents:
        newsletter: 
            defaultUrlPrefix: 'https://my-host.com'
```

## Newsletter Frontend Framework

Once you have setup your data class, it's possible to use the newsletter frontend framework. 
This simple framework allows you to create a hassle free subscribe/confirm/unsubscribe workflow.
 
## Example
 
### Controller

For example, `\NewsletterController`: `src/Controllers/NewsletterController.php`

```php
<?php

namespace App\Controller; 

use Pimcore\Controller\FrontendController;
use Pimcore\Model;
use Pimcore\Tool\Newsletter;
use Symfony\Component\HttpFoundation\Request;


class NewsletterController extends FrontendController
{
    public function subscribeAction(Request $request)
    {
        $newsletter = new Newsletter("person"); // replace "person" with the class name you have used for your class above (mailing list)
        $params = $request->request->all();

        $success = false;

        if ($newsletter->checkParams($params)) {
            try {
                $params["parentId"] = 1; // default folder (home) where we want to save our subscribers
                $newsletterFolder = Model\DataObject::getByPath("/crm/newsletter");
                if ($newsletterFolder) {
                    $params["parentId"] = $newsletterFolder->getId();
                }

                $user = $newsletter->subscribe($params);

                // user and email document
                // parameters available in the email: gender, firstname, lastname, email, token, object
                // ==> see mailing framework
                $newsletter->sendConfirmationMail($user, Model\Document::getByPath("/en/advanced-examples/newsletter/confirmation-email"), ["additional" => "parameters"]);

                // do some other stuff with the new user
                $user->setDateRegister(new \DateTime());
                $user->save();

                $success = true;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        return $this->render('Newsletter/subscribe.html.twig', ['success' => $success]);
    }

    public function confirmAction(Request $request)
    {
        $success = false;

        $newsletter = new Newsletter("person"); // replace "person" with the class name you have used for your class above (mailing list)

        if ($newsletter->confirm($request->get("token"))) {
            $success = true;
        }

        return $this->render('Newsletter/confirm.html.twig', ['success' => $success]);
    }

    public function unsubscribeAction(Request $request)
    {
        $newsletter = new Newsletter("person"); // replace "person" with the class name you have used for your class above (mailing list)

        $unsubscribeMethod = null;
        $success = false;

        if ($request->get("email")) {
            $unsubscribeMethod = "email";
            $success = $newsletter->unsubscribeByEmail($request->get("email"));
        }

        if ($request->get("token")) {
            $unsubscribeMethod = "token";
            $success = $newsletter->unsubscribeByToken($request->get("token"));
        }

        return $this->render('Newsletter/unsubscribe.html.twig', [
            'success' => $success,
            'unsubscribeMethod' => $unsubscribeMethod, 
        ]);
    }
}
```

### Views

The subscribe action view: `templates/Newsletter/subscribe.html.twig`

```twig
{% extends 'layout.html.twig' %}
{% set request = app.request %}

{% if not success %}
    {% if request.get('submit') %}
        <div class="alert alert-danger">
            {{ "Sorry, something went wrong, please check the data in the form and try again!"|trans }}
        </div>
        <br />
        <br />
    {% endif %} 
    
    <form class="form-horizontal" role="form" action="" method="post">
        <div class="form-group">
            <label class="col-lg-2 control-label">{{ "Gender"|trans }}</label>
            <div class="col-lg-10">
                <select name="gender" class="form-control">
                    <option value="male"{% if request.get('gender') == 'male' %} selected="selected"{% endif %}>{{ "Male"|trans }}</option>
                    <option value="female"{% if request.get('gender') == 'female' %} selected="selected"{% endif %}>{{ "Female"|trans }}</option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">{{ "Firstname"|trans }}</label>
            <div class="col-lg-10">
                <input name="firstname" type="text" class="form-control" placeholder="" value="{{ request.get('firstname') }}">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">{{ "Lastname"|trans }}</label>
            <div class="col-lg-10">
                <input name="lastname" type="text" class="form-control" placeholder="" value="{{ request.get('lastname') }}">
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-2 control-label">{{ "E-Mail"|trans }}</label>
            <div class="col-lg-10">
                <input name="email" type="text" class="form-control" placeholder="example@example.com" value="{{ request.get('email') }}">
            </div>
        </div>

        <br />

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <input type="submit" name="submit" class="btn btn-default" value="{{ "Submit"|trans }}">
            </div>
        </div>
    </form>
{% else %} 
    <div class="alert alert-success">{{ "Success, Please check your mailbox!"|trans }}</div>
{% endif %}
```

The confirm action view: `templates/Newsletter/confirm.html.twig`

```twig
{% extends 'layout.html.twig' %}

{% if not success %}
    <div class="alert alert-danger">
        <h2>{{ "Sorry, something went wrong, please sign up again!"|trans }}</h2>
    </div>
{% else %} 
    <div class="alert alert-success">
        <h2>{{ "Thanks for confirming your address!"|trans }}</h2>
    </div>
{% endif %}
```

The unsubscribe action view: `templates/Newsletter/unsubscribe.html.twig`

```twig
{% extends 'layout.html.twig' %}
{% set request = app.request %}

{% if not success %}

    {% if unsubscribeMethod %}
        <div class="alert alert-danger">
            {% if unsubscribeMethod == 'email' %}
                Sorry, we don't have your address in our database.
            {% else %} 
                Sorry, your unsubscribe token is invalid, try to remove your address manually:
            {% endif %}
        </div>
    {% endif %}


    <form class="form-horizontal" role="form" action="" method="post">

        <div class="form-group">
            <label class="col-lg-2 control-label">{{ 'E-Mail'|trans }}</label>
            <div class="col-lg-10">
                <input name="email" type="text" class="form-control" placeholder="example@example.com" value="{{ request.get('email') }}">
            </div>
        </div>

        <br />

        <div class="form-group">
            <div class="col-lg-offset-2 col-lg-10">
                <input type="submit" name="submit" class="btn btn-default" value="{{ 'Submit'|trans }}">
            </div>
        </div>
    </form>
{% else %} 
    <div class="alert alert-success">
        <h2>{{ 'Unsubscribed'|trans }}</h2>
    </div>
{% endif %}
```

### Confirmation E-Mail

The confirmation e-mail (See: `\Pimcore\Tool\Newsletter::sendConfirmationMail`) is a simple e-mail document.

In this document the following Twig parameters are available:

* `{{ firstname }}`
* `{{ lastname }}`
* `{{ token }}`
* `{{ email }}`
* `{{ gender }}`
* `{{ object.someMethod }}`

In the document editmode you can create the confirmation email by choosing **Add email -> Standard-Mail**:

![Create email document](../img/newsletter_create_document_confirmation.png)

The editmode is of course quite similar to other document types:

![Editing the email document](../img/newsletter_confirmation_document_editmode.png)


To define the confirmation URL in editmode. You have to add controller / action and token information.
In the picture, you can see how you would add a token  to the URL. 

![The email document - a variable placeholder](../img/newsletter_token_url.png)


### Sending Newsletters

#### Create a Newsletter Document

To send a newsletter you need an email document, which is used as content for the newsletter (nothing special to consider).
 
In this document the following Twig parameters are available:

* `{{ firstname }}`
* `{{ lastname }}`
* `{{ token }}`
* `{{ email }}`
* `{{ gender }}`
* `{{ object.someMethod }}`


![Create newsletter document](../img/newsletter_create_newsletter_document.png)

#### Get UserData in the Email Template

```php
$userObject = $this->getParam('object');
$firstname = $userObject->getFirstname();
$lastname = $userObject->getLastname();
...
```

In the example below, you can find out how to add the unsubscribe link and how to use firstname in the content.

![Newsletter - mailing, using variables](../img/newsletter_mailing_example.png)

### Send a Test Message

In newsletter documents, there is additional tab in the top panel, it's called *Newsletter Sending Panel*.
It's a good practise to check newsletter before it would be used with real emails. 

![Send a test newsletter](../img/newsletter_test_sending.png)

### Send the Newsletter

To send the newsletter you have to specify a source adapter.
We're going to use customer objects as a source, therefore we need to choose the *Default object list* adapter.
And the *Customer* class as the source.

Choose the adapter:

![Newsletter - objects list source adapter](../img/newsletter_objects_list_adapter.png)

Choose the class:

![Newsletter - objects list source adapter class](../img/newsletter_objects_list_adapter_class.png)

and at the end, just push the **Send Newsletter Now** button.
