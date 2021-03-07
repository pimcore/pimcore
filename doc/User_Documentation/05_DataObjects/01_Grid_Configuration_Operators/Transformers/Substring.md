# Substring

![Symbol](../../../img/gridconfig/operator_substring_symbol.png)

Interprets child element as string and extracts the defined sub string from it. If child element
returns an array, an array of sub strings is created by the operator. 

Configuration Settings: 
- Label: Label of column.
- Start: Start for sub string.
- Length: Length of sub string.
- Ellipses: Add ellipses if original string is longer than defined substring. 


Sample: 

![Sample](../../../img/gridconfig/operator_substring_sample.png)

Start at 3, grab 4 characters and add `...` if the original string was longer than 7 characters.




