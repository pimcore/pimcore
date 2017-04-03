# Date Datatypes

## Date, Date & Time

The `date` and `date & time` object fields are represented by a calender widget in the Pimcore GUI. 

![Date Field](../../../img/classes-datatypes-date1.jpg)

In the database its data is saved as unix timestamp and thereby stored in an INT data column. Programmatically 
these data types are represented by a [DateTime/Carbon](https://github.com/briannesbitt/Carbon) Object.


## Time

The `time` data field is the same drop down list of day times as in the `date & time` field.

![Time Field](../../../img/classes-datatypes-date2.jpg)

It's stored as a string in a VARCHAR(5) column in the database and can be set programmatically by simply passing a 
string like for example "11:00" to the field's setter.


