# Magento 2 Eventbridge Notifier
This repository adds an [aligent/async-events](https://github.com/aligent/magento-async-events) compatible notifier for submitting events to [Amazon EventBridge](https://aws.amazon.com/eventbridge/).

## How to use
This module only provides an implementation of the `EventBridgeNotifier`. The `NotifierFactoryInterface` does not know anything about it yet. Therefore, it must be hooked up to the factory depending on the implementation of the `NotifierFactory`.

1. Hook the notifier to the notifier factory
   * This depends on how your factory is setup, if you're using the reference implementation for `NotifierFactoryInterface`, then you have to add the following lines
to a `di.xml`

```xml
<type name="Aligent\AsyncEvents\Service\AsyncEvent\NotifierFactory">
    <arguments>
        <argument name="notifierClasses" xsi:type="array">
            <item name="event_bridge" xsi:type="object">Aligent\EventBridge\Service\EventBridgeNotifier</item>
        </argument>
    </arguments>
</type>
```
2. Run `bin/magento cache:clear`
3. Create subscribers which use the new notifier using the `metadata` field.

Example
```sh
curl --location --request POST 'https://m2.dev.aligent.consulting:44356/rest/V1/async_event' \
--header 'Authorization: Bearer TOKEN' \
--header 'Content-Type: application/json' \
--data-raw '{
    "asyncEvent": {
        "event_name": "my.custom.hook",
        "recipient_url": "Amazon Event Bridge ARN",
        "verification_token": "supersecret",
        "metadata": "event_bridge"
    }
}'
```

### Configuring AWS Credentials
An IAM role with the `events:PutEvents` action is required so that the notifier can relay events into Amazon EventBridge.

Under `Stores -> Services -> Amazon EventBridge` set the `Access Key ID` and the `Secret Access Key` and the `Region`. You
can configure the source of the event and the event bus if necessary.

![AWS Config](./docs/config.png)
