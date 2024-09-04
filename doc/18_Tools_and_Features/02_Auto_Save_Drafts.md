# Auto Save Drafts
Pimcore has an auto save functionality enabled by default for Data Objects and Documents, which creates a draft on default interval of 60 seconds or as soon as there is a first change detected on a data object/document. 
This feature creates a draft version, which is not published and changes will appear only in backend until published.

It is possible to change interval or completely turn off auto save functionality(by setting interval to 0) in config.yaml:
```yaml
pimcore: 
    
    documents: 
        auto_save_interval: 60 # saving interval in seconds, default 60s, set to 0 to disable it
    
    objects: 
        auto_save_interval: 60 # saving interval in seconds, default 60s, set to 0 to disable it
```

> Note: When using Auto Save for a type, the "Disable unsaved content warning" checkbox in User profile will have no effect on the close action.
