# Checkout Manager

The Checkout Manager is responsible for all aspects concerning checkout process and an one-stop API for getting 
through the checkout process. 

But: The checkout manager is not an out-of-the-box checkout process! 
It is a tool and a set of components the developer can use to create a use case specific checkout process. 

> Starting with Pimcore 6.1 an optimized checkout manager architecture was introduced. This is parallel to the old 
> architecture, which is deprecated now and will be removed in Pimcore 7. For details see [Checkout Manager Details](08_Checkout_Manager_Details.md).

##### Involved Modules and their function
- **Controller**: Website Controller that controls the flow.
- **Cart**: Cart is the cart and user should be able to modify the cart all the time.
- **CheckoutManager**: One-Stop API for controller to manage checkout, start and handle payments and commit orders.
- **OrderManager**: API for working with orders - creating them, listing them, etc.  
- **OrderAgent**: API for manipulating single order, also responsible for status management of payments within order.
- **PaymentProvider**: Interface to payment provider.
- **CommitOrderProcessor**: Worker that handles all steps to commit order after payment was successfully finished by user. 


##### See following sub pages for detailed information 
- [Basic configuration of Checkout Manager](./01_Basic_Configuration.md)
- [Setting up Checkout Steps](./03_Checkout_Steps.md)
- [Committing Orders](./05_Committing_Orders.md)
- [Integrating Payment](./07_Integrating_Payment.md)
- [Using Checkout Tenants](./09_Checkout_Tenants.md)
