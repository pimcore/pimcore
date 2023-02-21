# Order Manager
The Order Manager is responsible for all aspects of working with orders except committing them (which is the 
responsibility of the Commit Order Processor). These aspects contain among other things:
* Creating orders based on carts
* Order Storage (by default as Pimcore objects)
* Loading orders 
* Loading order lists and filter them ([Order List](./01_Working_with_Order_Lists.md))
* Working with orders after order commit ([Order Agent](./02_Working_with_Order_Agent.md)) 


## Configuration
The configuration takes place in the `pimcore_ecommerce_framework.order_manager` config section and is [tenant aware](../04_Configuration/README.md).

```yaml
pimcore_ecommerce_framework:
    order_manager:
        tenants:
            _defaults:
                # service ID of order manager implementation
                order_manager_id: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderManager
                # options for oder manager
                options:
                    # Pimcore object class for orders
                    order_class: \Pimcore\Model\DataObject\OnlineShopOrder
                    # Pimcore object class for order items
                    order_item_class: \Pimcore\Model\DataObject\OnlineShopOrderItem
                    # Class for order listing
                    list_class: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing
                    # Class for order item listing
                    list_item_class: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\Listing\Item
                    # Default parent folder for new orders
                    parent_order_folder: /order/%%Y/%%m/%%d
                # Options for oder agent
                order_agent:
                    # service ID of order agent factory - builds order agents individual to each order
                    factory_id: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\Order\AgentFactory
                    # options for order agent factory - available options vary by factory implementation
                    factory_options:
                        agent_class: Pimcore\Bundle\EcommerceFrameworkBundle\OrderManager\V7\OrderAgent
            
            # inherits from _defaults
            default: ~
                        
            # inherits from _defaults, but sets another order folder
            otherFolder:
                options:
                    parent_order_folder: /order_otherfolder/%%Y/%%m/%%d
```
