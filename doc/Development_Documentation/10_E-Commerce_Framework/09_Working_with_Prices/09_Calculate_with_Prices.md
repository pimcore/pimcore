# Calculate with Prices

Calculating with prices is a delicate thing since one has to be very carefully with rounding issues. To take care about
 this, internally Price objects (`IPrice`) use a value object instead of floats to represent prices. These value objects 
 are called [Decimal](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Type/Decimal.php) 
 and use an integer representation with 4 digits after the comma as value (123.45 becomes 1234500). 
 
To calculate with these values, the provided methods like `add()`, `sub()`, `mul()`, `div()` and others have to be used. 
For details see the [Decimal class definition](https://github.com/pimcore/pimcore/blob/master/pimcore/lib/Pimcore/Bundle/EcommerceFrameworkBundle/Type/Decimal.php)
 
# Aspects good to Know
- In order to have sufficient number of digits in integer datatype, your system should run on 64 bit infrastructure. 
On 32 bit systems, you would be able to handle prices up to 214,748.3647 only (since max int value with 32 bit is 2,147,483,647) 
 
 