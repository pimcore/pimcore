# Migrate your Application to Symfony Stack

This migration guide gives you a checklist for migrating a typical Pimcore 4 application to
the new Symfony stack. This checklist can only be a starting point and never be a complete
list for every Pimcore application. Depending on your application additional steps will 
be necessary. 

### Prerequisites 
- If not already done, following database changes are needed: 
```sql 
ALTER TABLE `documents_page` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `personas`;
ALTER TABLE `documents_snippet` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `contentMasterDocumentId`;
ALTER TABLE `translations_website` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin';
ALTER TABLE `translations_admin` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin'; 
```

### Controller
- Move Controllers to `/src/AppBundle/Controllers/`.
- Add namespace to controllers and extend them from `Pimcore\Controller\FrontendController` 
- Controllers now live in namespaces. As a consequence everything in default namespace 
(like Exception, stdClass, ...) need to be prefixed with `\`.
- `init()` is not available anymore. Migrate all content, most likely to `onKernelController()`. 
- `preDispatch()` is not available anymore. Migrate all content,  most likely to `onKernelController()`. 
- To assign current language (or any other variable) to view for all actions (as before done in 
`\Website\Controller\Action` of Pimcore demo) use `onKernelController` function in the controller and following lines: 
```php
<?php 
public function onKernelController(FilterControllerEvent $event)
{
    // enable view auto-rendering
    $this->setViewAutoRender($event->getRequest(), true, 'php');

    //get first two characters of locale for language
    $this->view->language = substr($event->getRequest()->getLocale(), 0, 2);
}
``` 
- Remove `$this->enableLayout()`. Use `$this->extend` in view template instead, see 
[layouts](../../../02_MVC/02_Template/00_Layouts.md) for details. 
- Replace `$this->getParam()` by adding `Request $request` as action Parameter and call 
`$request->get()` instead. 
- Replace `$this->getAllParams()` by adding `Request $request` as action Parameter and call 
`$request->query->all()` for all GET params and `$request->request->all()` for all POST params. 
- Replace `$this->getRequest()->isPost` with `$request->getMethod() == 'POST'`. 
- `$this->redirect` now needs a return statement like  `return $this->redirect($url);`. 
- View helpers are not available in controller anymore. If you need to use them in controller 
actions, get them from Symfony container follows. They all have keys like `pimcore.templating.*`.
```php
<?php
	$headTitle = $this->get('pimcore.templating.view_helper.head_title');
	$headTitle($category->getName());
```

- Replace ZF1 `Zend_Paginator` with ZF3 Paginator. It should be sufficient just to replace the create
statement with `$paginator = new Paginator($productList);`. 


### Area Bricks
- Create an AreaBrick class in `/src/AppBundle/Document/Areabrick/GalleryCarousel.php`.
- Move/create view to/in `/app/Resources/views/Areas/gallery-carousel/view.html.php`. 
- Clear cache in order to update the container and register the new Area Brick. 
- **Do not delete/move old area bricks and save documents with them afterwards**. 
This will result in data loss!
- For details see [area brick docs](../../../03_Documents/01_Editables/02_Areablock/02_Bricks.md).

### Views

- Layouts
   - Move layouts to `/app/Resources/views` and optionally sub folders. 
   - Replace `<?= $this->layout()->content; ?>` with `<?php $this->slots()->output('_content') ?>`
   - Use `$this->extend('layout.html.php');` for including layouts in templates
   - Do not call `$this->extend('layout.html.php');` twice, for example via including templates via `$this->render()`.
    This will result in double rendering of the template (e.g. two menus, ...). 
   - For details on layouts see [layout docs](../../../02_MVC/02_Template/00_Layouts.md). 

- Templates location
  - move view scripts to `/app/Resources/views/[ControllerName]/[actionName].html.php` 
    - Controller name now Uppercase
    - Template name now `actionName.html.php` instead of `action-name.php` (camel case & html.php)
    - For details on templates see [template docs](../../../02_MVC/02_Template/README.md).
  - Optionally add following comment to top of each template (will give you type hinting and autocomplete in your IDE)
```php
<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>
```

- Templating Helpers
  - Template helpers for including templates
    - They now follow the Symfony notation for templates, `$this->template('/includes/language.php')` becomes 
      `$this->template('Includes/language.html.php');` or `$this->template(":Includes:content-headline.html.php");`
    - `$this->template` needs echo its content - e.g. `<?= $this->template(":Includes:content-headline.html.php"); ?>` 
    - Replace `$this->partial` with `$this->template`
    - In `$this->action` you need to pass all request params that are needed in sub request
     manually, this doesn't happen automatically any more. The passed parameters are stored 
     as attributes in sub request then. 

  - Navigation Changes
     - Replace `$this->pimcoreNavigation` with `$this->navigation`. 
     - In your navigation partial scripts, use `$this->pages` instead of `$this->container` (reserved for DI-container)
     - Replace `Pimcore\Navigation\Page\Uri` with `Pimcore\Navigation\Page\Document`. 
     - Every thing else should remain the same. 

   - Replace all `$this->url()` with `$this->pimcoreUrl()`, parameters and behaviour stay the same. 
   You also could use Symfony standard helpers `$this->path()` and `$this->url()`, but there you also 
   take care of parameter and behavior changes. 

- Paging
  - Replace `$this->paginationControl($this->paginator, 'Sliding', 'shop/includes/pagination.php' );` with 
     `$this->render("Shop/includes/pagination.html.php", get_object_vars($this->paginator->getPages("Sliding")));` 
  - Replace `$this->current` in pagination snippet with `$current` - `$this->current` has different meaning now. 

- Other stuff
  - For getting current language use `$this->request()->getLocale()` (or set language as view variable in controller). 
  - Replace `$this->t` with `$this->translate`

### Custom Models and Libraries
- All stuff that lives in `/website/lib` and `/website/models` can be moved to the source 
 folder and should be included automatically by the autoloader. E.g. you have a 
 directory `/src/Website` for all your models and libraries in `Website` namespace. 
- Replace and refactor all ZF1 functionality. 

> Also have a look at the [Upgrade Notes](../../09_Upgrade_Notes/02_V4_to_V5.md).
