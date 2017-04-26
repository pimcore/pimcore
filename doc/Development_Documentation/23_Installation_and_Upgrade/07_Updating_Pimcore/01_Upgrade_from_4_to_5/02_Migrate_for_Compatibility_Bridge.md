# Get your application up and running with the `Compatibility Bridge` of Pimcore 5
Pimcore 5 ships with a `Compatibility Bridge` that should enable Pimcore 5 to run Pimcore 4 applications with some file 
moves and minor code updates.
This guide describes the steps needed in detail. 
  
- **Backup your system!** 
- Execute the [migration.sh](./migration.sh) script with following sytax `./migration.sh <PATH TO PIMCORE ROOT>`. 
This script does following things for you: 
  - move old `pimcore` folder to `pimcore4`
  - download latest Pimcore 5 build and extract it
  - create several necessary directories
  - move config files to new locations
  - move class files to new location
  - move versions to new location
  - move logs to new location
  - move email logs to new location
  - move assets to new location
  - move website folder to /legacy/website
  - move plugins folder to /legacy/plugins
  - run composer update

- Refactor `constants.php` and move it to `app/constants.php`
- Refactor `startup.php` and move content either to `AppKernel::boot()` or `AppBundle::boot()`
- Update system configs in `/var/config/system.php`
    - `email` > `method` => if `''` change to `null`
    - `email` > `smtp` > `ssl` => if `''` change to `null`
    - `email` > `smtp` > `auth` > `method` => if `''` change to `null`
    - `email` > `smtp` > `auth` > `password` => add if not there with value `''`
    - `newsletter` > `method` => if `''` change to `null`
    - `newsletter` > `smtp` > `ssl` => if `''` change to `null`
    - `newsletter` > `smtp` > `auth` > `method` => if `''` change to `null`
    - `newsletter` > `smtp` > `auth` > `password` => add if not there with value `''`


- Probably you need to fix some of your Plugins - e.g. remove calls to `$db->describeTable('tablename');`
- Add symlinks to static files, e.g.: 
  - Add Symlink in `/web/website` to `../../legacy/website/static`
  - Add Symlinks in `/web/` to your static plugin directories

- Change document root of your webserver to `/web` directory - document root must not be project root anymore

- Run `composer update` once more if it failed before. Now it should run through, other wise you have to fix the 
remaining problems. 

- Run following database updates: 
```sql 
ALTER TABLE `documents_page` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `personas`;
ALTER TABLE `documents_snippet` ADD COLUMN `legacy` TINYINT(1) NULL AFTER `contentMasterDocumentId`;
ALTER TABLE `documents_newsletter` ADD COLUMN `legacy` TINYINT(1) NULL;
ALTER TABLE `documents_printpage` ADD COLUMN `legacy` TINYINT(1) NULL;
ALTER TABLE `documents_email` ADD COLUMN `legacy` TINYINT(1) NULL;
ALTER TABLE `translations_website` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin';
ALTER TABLE `translations_admin` CHANGE COLUMN `key` `key` VARCHAR(190) NOT NULL DEFAULT '' COLLATE 'utf8mb4_bin'; 
```

- Change your Pimcore Documents to legacy mode with following DB statements and clear the Pimcore cache afterwards: 
```sql
UPDATE documents_page SET legacy = 1; 
UPDATE documents_snippet SET legacy = 1;
```

- Start Pimcore backend in your browser (`<yourdomain>/admin`).
- Change `StaticRoutes` to `legacy mode` by opening static routes dialog and ticking the `legacy` checkbox
- Activate debug mode again (if it was activated) in system settings. 
- Reconfigure e-mail sending settings in system settings

- If you were still using `Zend_Date` in your application using the flag `useZendDate` in your `system.php`, 
you have to enable the support in `app/config/config.yml` by setting `pimcore -> flags -> zend_date` to `true`. 

Now your application should be up and running again with the `Compatibility Bridge` and the old ZF1 stack. 
The next step would be to migrate the application step by step to new Symfony stack to take advantage of the full power 
of Pimcore 5. 



### Additional steps if you are using the old EcommerceFramework Plugin 
The EcommerceFramework Plugin is now integrated into Pimcore 5 core. To migrate ecommerce applications that use the old 
EcommerceFramework Plugin (latest release), following additional steps are needed: 
- Remove `EcommerceFramework` Plugin from `/legacy/plugins` folder.
- Add following lines to the `extensions.php`
```php 
    "bundle" => [
        "Pimcore\\Bundle\\EcommerceFrameworkBundle\\PimcoreEcommerceFrameworkBundle" => TRUE,
    ]
```
- Move `OnlineShopConfig.php` (and all sub files) to `/app/config/pimcore` and rename it to EcommerceFrameworkConfig.php
- Modify `EcommerceFrameworkConfig.php`: 
   - change root node `onlineshop` to `ecommerceframework`
   - change `defaultlocale` to `defaultCurrency` and enter ISO code of currency.
   - adapt all `file` sections to new paths of sub files (relative to `/app/config/pimcore`).
- Run following database updates: 
```sql 
RENAME TABLE plugin_onlineshop_cart TO ecommerceframework_cart; 
RENAME TABLE plugin_onlineshop_cartcheckoutdata TO ecommerceframework_cartcheckoutdata; 
RENAME TABLE plugin_onlineshop_cartitem TO ecommerceframework_cartitem; 
RENAME TABLE plugin_onlineshop_pricing_rule TO ecommerceframework_pricing_rule; 
RENAME TABLE plugins_onlineshop_vouchertoolkit_reservations TO ecommerceframework_vouchertoolkit_reservations;
RENAME TABLE plugins_onlineshop_vouchertoolkit_statistics TO ecommerceframework_vouchertoolkit_statistics;
RENAME TABLE plugins_onlineshop_vouchertoolkit_tokens TO ecommerceframework_vouchertoolkit_tokens;

UPDATE translations_admin SET `key` = REPLACE(`key`, 'plugin_onlineshop_', 'bundle_ecommerce_') WHERE `key` LIKE 'plugin_onlineshop%';
UPDATE users_permission_definitions SET `key` = REPLACE(`key`, 'plugin_onlineshop_', 'bundle_ecommerce_');
UPDATE users SET permissions = REPLACE(`permissions`, 'plugin_onlineshop_', 'bundle_ecommerce_');

-- possibly you need to update some of these tables (or additional product index tables) too - depends on you configuration 
RENAME TABLE plugin_onlineshop_productindex TO ecommerceframework_productindex; 
RENAME TABLE plugin_onlineshop_productindex_relations TO ecommerceframework_productindex_relations; 
RENAME TABLE plugin_onlineshop_productindex_store TO ecommerceframework_productindex_store; 
RENAME TABLE plugin_onlineshop_optimized_productindex TO ecommerceframework_optimized_productindex; 
RENAME TABLE plugin_onlineshop_optimized_productindex_relations TO ecommerceframework_optimized_productindex_relations;
```
- migrate translations for order backoffice to adminTranslations
- extend your payment page with following or a similar snippet if needed: 
```php
<?php if ($form instanceof \Symfony\Component\Form\FormBuilderInterface) { ?>
    <p><img src="https://www.wirecard.at/fileadmin/templates/images/wirecard-logo.png"/></p>

    <p><?= $this->translate('checkout.payment.txt') ?></p>

    <?php
        $form->remove('submitbutton');
        $form->add('submitbutton', \Symfony\Component\Form\Extension\Core\Type\SubmitType::class, ['attr' => ['class' => 'btn btn-primary'], 'label' => $this->translate('checkout.payment.paynow')]);
        $container = \Pimcore::getContainer();
        echo $container->get('templating.helper.form')->form($form->getForm()->createView());
    ?>

    <script type="text/javascript">
        $(document).ready(function () {
           document.getElementById('paymentForm').submit();
        });
    </script>

<?php } else if ($form instanceof \Zend_Form) { ?>
```
- run additional `assets:install` command in order to update symlinks in `web/bundles` folder
- migrate content of `app/config/pimcore/di.php` to `app/config/config.yml`, see [Overriding Models](../../../20_Extending_Pimcore/03_Overriding_Models.md) for details. 
