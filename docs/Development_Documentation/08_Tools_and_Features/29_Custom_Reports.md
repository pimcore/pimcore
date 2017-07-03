# Custom Reports

Custom Reports is a report engine directly integrated into Pimcore. With Custom Reports it is possible to create tabular
or chart reports (or both) with further filtering and export functionality. 

![Custom Reports](../img/custom-reports.png)

The data source for the reports is always a source adapter which is responsible for retrieving and preparing the report
data. Currently two adapters ship with Pimcore: 
- SQL: Retrieve Data based on a SQL statement
![Custom Reports Configuration](../img/custom-reports-config.png)
- Google Analytics: Retrieve Data from Google Analytics 


## Custom Data Source Adapters
It is easily possible to implement custom source adapters for special use cases. To do so following steps are necessary: 

- JavaScript Class: This class defines the user interface in the configuration of the custom report. It has to be located in 
the namespace `pimcore.report.custom.definition`, named like the adapter (e.g. `pimcore.report.custom.definition.mySource`)
 and implement the methods `initialize`, `getElement` and `getValues`. As sample see [analytics](https://github.com/pimcore/pimcore/blob/pimcore4/pimcore/static6/js/pimcore/report/custom/definitions/analytics.js)
- PHP Class: This class is the server side implementation of the adapter. It is responsible for retrieving and preparing 
the options, columns and data. It has to be located in the namespace `Pimcore\Model\Tool\CustomReport\Adapter`, named like
the adapter (e.g. `MySource`) and extend the abstract class `Pimcore\Model\Tool\CustomReport\Adapter\AbstractAdapter`. As sample see
 [analytics adapter](https://github.com/pimcore/pimcore/blob/pimcore4/pimcore/models/Tool/CustomReport/Adapter/Analytics.php). 
