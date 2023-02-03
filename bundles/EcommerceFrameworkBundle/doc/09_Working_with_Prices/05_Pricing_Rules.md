## Pricing Rules
Pricing Rules are supported by the E-Commerce Framework out of the box. The pricing rules themselves can be configured 
in the Pimcore Backend UI by putting conditions and actions together. 

![Pricing Rules](../img/pricing-rules.png)

Once active, all rules are checked and applied automatically by the system - including reducing product prices, adding 
price modificators to reduce cart totals, removing shipping costs and adding gift items to the cart. 

To print the applied rules in the frontend, the developer needs to add some lines of code. Depending on the location, 
following scripts can be used. 


#### Product Detail Page
```twig
{% set priceInfo = product.OSPriceInfo %}
{% if priceInfo.rules %}
	<div class="discounts">
		<p><strong>{{ 'shop.detail.your_benefit'|trans }}</strong></p>
		<ul>
			<?php foreach($priceInfo->getRules() as $rule ) { ?>
            {% for rule in priceInfo.rules %}
				<?php foreach($rule->getActions() as $action) { ?>
                {% for action in rule.actions %}
                    {% if action is instanceof('Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\ProductDiscount') %}
                        {% if action.amount > 0 %}
							<li>{{ rule.label }} {{ 'shop.detail.your_benefit.discount.amount'|trans([action.amount]) }}</li>
                        {% elseif action.percent > 0 %} 
							<li>{{ rule.label }} {{ 'shop.detail.your_benefit.discount.percent'|trans([action.percent]) }}</li>
						{% endif %}
					{% elseif action is instanceof('Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\GiftInterface') %}
                        <li>
                            {{ 'shop.detail.your_benefit.discount.gift'|trans }}, <a href="{{ action.product.getShopDetailLink() }}">{{ action.product.name }}</a>
                        </li>
                    {% elseif action is instanceof('Pimcore\Bundle\EcommerceFrameworkBundle\PricingManager\Action\FreeShipping') %}
						<li>
							{{ 'shop.detail.your_benefit.discount.freeshipping'|trans }}
						</li>
					<?php } ?>
				{% endfor %}
			{% endfor %}
		</ul>
	</div>
{% endif %}
```


#### Cart Product List - Printing Gift Items
```twig
{% for item in car.giftItems %}
    {% set linkDetail = item.product.shopDetailLink %}
	<tr>
		<td class="cart-list-items-image">
			<a href="{{ linkDetail }}" >
				<img src="{{ item.product.firstImage({width: 120, height: 120, aspectratio: true}) }}" alt="" border="0" />
			</a>
		</td>
		<td class="cart-list-items-name" valign="top">
			<a href="{{ linkDetail }}" >{{ item.product.OSName }}</a>
		</td>
		<td class="cart-list-items-quantity">
            {{ item.count }}
		</td>
	</tr>
{% endfor %}
```

> All other price modifications on cart level are included as cart price modificators. 
> See [Cart manager](../11_Cart_Manager.md) for more details and how to print them. 
