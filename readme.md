# Magento2 Webhook Module

This is an example module that will enable sending Placed Order events from Magento2 to a webhook endpoint. The event send is triggered by the `checkout_onepage_controller_success_action`, which is dispatched when a one page checkout success page is rendered ([Adobe docs](https://developer.adobe.com/commerce/php/development/components/events-and-observers/event-list/#checkout_onepage_controller_success_action)).

## Configuration

You need to create a private API key with full access to events.

In [DataMap.php](Webhook/Webhook/Helper/DataMap.php) there is one required variable to change:

```
const WEBHOOK_API_KEY = "PRIVATE_API_KEY_GOES_HERE";
```
Enter the created private API key there.

Two other variables that can be changed are:
* `PLACED_ORDER` - the metric name the placed order event will be created under. The default is `Placed Order Webhook`.
* `REVISION` - the API revison to use. This will need to be updated every two years to be compliant with the webhook provider's deprecation policy.