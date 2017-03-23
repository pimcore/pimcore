# Database Model

Pimcore tries to keep a clean and optimized database model for managing the data. Nevertheless 
there are quite a lot tables around and finding the correct data might be a bit challenging at
the beginning. 

Basically there are two types of tables
* Default tables with are created during install - for all sorts of data like assets, documents
logs, versions, ... 
* Dynamically created tables during object data model configuration - mainly for all object related
data. 


# Default Tables 
These tables are created during Pimcore install and are always the same. 

| Table | Description |
|-------|-------------|
| application_logs | Contains all application logs. For more information see [Application Logger](../08_Tools_and_Features/17_Application_Logger.md). Additionally there might be application_logs_archive* tables for old logging entries. | 
| assets | Assets (Images, etc.), with system metadata |
| assets_metadata | Additional user metadata (Metadata tab in the asset panel) |
| cache | serialized data, used by the default Pimcore cache |
| cache_tags | Tag store for cache entries. Used by some cache backends |
| classes | List of all object classes with ID and name. Everything else is stored in php configuration files. |
| classificationstore_collectionrelations | Relation Collection - Group for Classification Store | 
| classificationstore_collections | Collections of Classification Store |
| classificationstore_groups | Groups of Classification Store |
| classificationstore_keys | Keys of Classification Store |
| classificationstore_relations | Relation Group - Key for Classification Store |
| classificationstore_stores | Stores of Classification Store |
| custom_layouts | Definition of the `custom layouts` for object classes |
| dependencies | Stores dependencies between elements such as objects, assets, documents |
| documents | List of all documents, folders, links, hardlinks, emails and snippets of the document area with meta- and config-data, relations |
| documents_elements | Editables of documents (data), in a serialized form |
| documents_email | Extra config data |
| documents_hardlink | Extra config data |
| documents_link | Extra config data |
| documents_newsletter | Extra config data |
| documents_page | Extra config data |
| documents_printpage | Extra config data |
| documents_snippet | Extra config data |
| documents_translations | Relation between same documents for different languages |
| edit_lock | Tracks which user opened which ressource in the backend |
| element_workflow_state | Keeps track of workflow state for all elements |
| email_blacklist | Blacklist for eMail-addresses
| email_log | Log for sent emails |
| glossary | Words to auto-link in texts. See [Glossary](../08_Tools_and_Features/21_Glossary.md) |
| http_error_log | HTTP error log |
| locks | Store for [Locking functionality](./17_Static_Helpers.md#locking) |
| notes | [Notes](../08_Tools_and_Features/05_Notes_and_Events.md) for elements | 
| notes_data | Additional data for notes | 
| objects | List of all objects with metadata like id, class name, path, parent, ...|
| properties | Data from the `properties` tab | 
| quantityvalue_units | Available quantites for quantity value object data type |
| recyclebin | Stores metadata of deleted elements |
| redirects | Stores redirects | 
| sanitycheck | Working table for Pimcore's sanity check |
| schedule_tasks | Stores scheduled tasks |
| search_backend_data | Stores the index for the backend search - is a InnoDb Table with fulltext capabilities |
| sites | Stores [sites](../02_MVC/04_Routing_and_URLs/08_Working_with_Sites.md) |
| tags | Stores available [tags](../08_Tools_and_Features/09_Tags.md)
| tags_assignment | Stores assignment of tags to elements |
| tmp_store | Pimcore internal tmp store | 
| tracking_events | |
| translations_admin | Backend translations |
| translations_website | Frontend translations |
| tree_locks | Locks in tree of Pimcore backend interface | 
| users | Backend users |
| users_permission_definitions | List of globally assignable user permissions |
| users_workspaces_asset | Stores user access permissions for asset folder |
| users_workspaces_document | Stores user access permissions for document folders |
| users_workspaces_object | Stores user access permissions for object folders |
| uuids | stores Unique Identifiers - if enabled |
| versions | List of object/asset/document versions. Actual data is serialized and written to disk |
| website_settings | Stores `Website Settings` |


# Object Tables 
These tables are created and modified dynamically during configuration of object data model. 
As a result they look different of every Pimcore installation depending of the data model. 

### Objects
As soon as a new object class is created in Pimcore, at least three tables are 
added to the database. The tables have a numerical suffix, denoting the number 
(id) of the object class: `object_query_(id)`, `object_relations_(id)`, 
`object_store_(id)` and an additional database view `object_(id)` which is a combination of 
`object_query_(id)` and `objects`. 
 
| Table / View | Description |
|-------|-------------|
| object_(id) View | Database view joining object_store_(id) and objects table |
| object_query_(id) Table | Use this table to retrieve data incl. inherited data. Data types with relations are usually stored in a serialized form here, too. Pimcore Object-Lists work with this table. |
| object_relations_(id) Table | Contains data of fields with relations to objects, assets, etc. |
| object_store_(id) Table | This is the main data storage table of an object class. It contains all "flat" data without any relations or external dependencies. |
| objects Table | Contains an entry for each and every object in the system. The id field is an auto_increment and the source of the primary key for an object. Metadata about an object is stored in this table, too. |


#### Simple Data Field Types
Following is an overview of how different object data types are stored in to database. This overview might not be complete.
This overview might be a useful starting point when querying object data with object lists. 


##### Text
Table: object_store_(id)
 
| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Input | varchar(255) | NULL | / |
| Textarea | longtext | NULL | / |
| wysiwyg | longtext | NULL | Text with HTML-tags |
| password | varchar(255) | NULL | Passwort - as hash |


##### Number
Table: object_store_(id)
 
| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Number | double/decimal(64,3) | NULL | Datatype depends on selected precision |
| Slider | double | NULL | / |


##### Date 
Table: object_store_(id)

| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Date | bigint(20) | NULL  | < 1970 = negative Timestamp |
| Date & Time | bigint(20) | NULL | < 1970 = negative Timestamp |
| Time | varchar(5) | NULL | String - e.g.: "12:00" |


##### Select 
Table: object_store_(id)
 
| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Select | varchar(255) | NULL | Selected value |
| User | varchar(255) | NULL | Pimcore User-ID |
| Country | varchar(255) | NULL | Country code |
| Language | varchar(255) | NULL | Language code |
| Multiselection | text | NULL | String, selected values, separated by "," |
| Countries (Multiselect) | text | NULL | String, selected language-codes, separated by "," |
| Languages (Multiselect) | text | NULL | String, selected language-codes, separated by "," |


##### Relations
Table: object_relations_(id) & object_meta_data_(id)

* Data fields of relation types are stored in extra tables
* Data fields are not stored in distinct columns, but as rows whereas the field name is in an extra column `fieldname`
* The column `type` specifies the type of the linked resource (Object, Document, Asset)
* The columns `src_id` and `dest_id` define the relation / the link between the objects. 
* Column `index` is used to specify the order of the relations
* Columns `ownertype`, `ownername` and `position` are used when relations are within field collections, localized fields, object bricks, etc.  
* The data type `Objects With Metadata` stores the extra data in a table `object_meta_data_(id)` - the column `column` 
specifies the name of the meta item and `data` stores the value


##### Structured

| Name | Comment |
| ---- | ------- |
| Table | Table data is stored as a string - serialized. |
| Structured Table | Each table cell is stored distinctively; schema: (fieldname)__(row key)#(column key) |
| Field-Collections | see special data fields later | 
| Objectbricks | see special data fields later | 
| Localized Fields | see special data fields later |


##### Geographic
Table: object_store_(id)
 
| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Geographic Point | double | NULL | Creates two columns: ‘(name)__longitude’ and ‘(name)__latitude’ |
| Geographic Bounds | double | NULL | Creates four columns: ‘(name)__NElongitude’, ‘(name)__NElatitude’, ‘(name)__SWlongitude’ und ‘(name)__SWlatitude’ | 
| Geographic Polygon | longtext | NULL | Serialized geo-data |


##### Other

| Name | Data Type | Default | Comment |
| ---- | --------- | ------- | ------- |
| Image | int(11) | NULL | ID of the image asset |
| Image Advanced | int(11), text | NULL | Creates a column `(name)__image`(int)  for the image assets id and the column `(name)__hotspots`(text). Hotspots are stored serialized. |
| Video | text | NULL | Serialized data |
| Checkbox | tinyint(1) | NULL | Boolean value (1 = true) |
| Link | text | NULL | Serialized data |


#### Special Data Fields

##### Objectbricks

| Table/View | Purpose |
| ---------- | ------- |
| object_brick_query_(id) Table | Analog to object_query_(id) |
| object_brick_store_(id) Table Main data storage |


##### Localized fields
 
| Table/View | Purpose |
| ---------- | ------- |
| object_localized_(id)_(language-code) View | A database view per language, combining regular and localized data fields |
| object_localized_data_(id) Table | Stores localized field data |
| object_localized_query_(id)_(language-code) Table | Analog to object_query_(id) |


##### Field Collections
 
| Table/View | Purpose |
| ---------- | ------- |
| object_collection_(collection-name)_(object-id) | Stores data of the field collections fields and the order (index) |
