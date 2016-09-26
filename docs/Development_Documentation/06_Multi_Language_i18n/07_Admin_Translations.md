# Admin Translations 

There is a View Helper available which allows you to use translations in your document editables and basically anything in your views.

#### Example: Translate Options of a Select Editable
```php
 <?= $this->select("select", [
     "store" => [
         ["option1", $this->translateAdmin("Option One")],
         ["option2", $this->translateAdmin("Option Two")],
         ["option3", $this->translateAdmin("Option Three")]
     ]
 ]); ?>
 ```
 
After adding a new translation, the document needs to be loaded once in editmode. This adds the new translation keys to 
*Extras* > *Admin Translations* where all extra translations can be edited. 

#### Shorthands 
```php
<?= $this->select("select", [
    "store" => [
        ["option1", $this->ts("Option One")],
        ["option2", $this->ts("Option Two")],
        ["option3", $this->ts("Option Three")]
    ]
]); ?>
 ```
 
