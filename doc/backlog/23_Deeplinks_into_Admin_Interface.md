# Deeplinks Into Admin-Interface 

Pimcore offers the possibility to deeplink elements inside the admin-interface from an external application. 
  
The link always follows the schema: `https://YOUR-HOST/admin/login/deeplink?TYPE_ID_SUBTYPE` 

## Examples 

#### Documents 
```text
https://acme.com/admin/login/deeplink?document_123_page 
https://acme.com/admin/login/deeplink?document_45_snippet 
https://acme.com/admin/login/deeplink?document_67_link 
https://acme.com/admin/login/deeplink?document_8_hardlink 
https://acme.com/admin/login/deeplink?document_9_email 
```

#### Assets 
```text
https://acme.com/admin/login/deeplink?asset_23_image 
https://acme.com/admin/login/deeplink?asset_34_document
https://acme.com/admin/login/deeplink?asset_56_folder
https://acme.com/admin/login/deeplink?asset_78_video
```

#### Objects 
```text
https://acme.com/admin/login/deeplink?object_24_object 
https://acme.com/admin/login/deeplink?object_98_variant 
https://acme.com/admin/login/deeplink?object_66_folder
```
