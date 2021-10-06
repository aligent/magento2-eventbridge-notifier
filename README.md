# Magento 2 Eventbridge Notifier
This repository adds an [aligent/magento2-webhooks](https://bitbucket.org/aligent/magento2-webhooks) compatible notifier for submitting events to [AWS EventBridge](https://aws.amazon.com/eventbridge/).

## How to use
This module only provides an implementation of the `EventBridgeNotifier`. The `NotifierFactoryInterface` does not know anything about it yet. Therefore, it must be hooked up to the factory depending on the implementation of the `NotifierFactory`.

1. Hook the notifier to the notifier factory
   * This depends on how your factory is setup, if you're using the reference implementation for `NotifierFactoryInterface`, then you have to add the following lines
to a `di.xml`

```xml
<type name="Aligent\Webhooks\Service\Webhook\NotifierFactory">
    <arguments>
        <argument name="notifierClasses" xsi:type="array">
            <item name="default" xsi:type="object">Vendor\Module\Service\HttpNotifier</item>
            <item name="event_bridge" xsi:type="object">Aligent\EventBridge\Service\EventBridgeNotifier</item>
        </argument>
    </arguments>
</type>
```
2. Run `bin/magento cache:clear`
3. Create subscribers which use the new notifier using the `metadata` field.

Example
```sh
curl --location --request POST 'https://m2.dev.aligent.consulting:44356/rest/V1/webhook' \
--header 'Authorization: Bearer TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "webhook": {
        "event_name": "my.custom.hook",
        "recipient_url": "https://recipient_url/custom_hook_handler",
        "verification_token": "supersecret",
        "metadata": "event_bridge"
    }
}'
```