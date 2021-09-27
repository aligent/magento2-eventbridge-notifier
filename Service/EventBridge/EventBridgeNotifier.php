<?php

namespace Aligent\EventBridge\Service\EventBridge;

use Aligent\Webhooks\Api\Data\WebhookInterface;
use Aligent\Webhooks\Helper\NotifierResult;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Aws\EventBridge\EventBridgeClient;
use Psr\Log\LoggerInterface;

/**
 * Class EventBridgeNotifier
 *
 * This norifier relays evetns into AWS EventBridge. A serverless event bus for building
 * event driven applications.
 *
 */
class EventBridgeNotifier implements NotifierInterface
{
    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var EventBridgeClient
     */
    private $eventBridgeClient;

    public function __construct(
        Json $json,
        EncryptorInterface $encryptor,
        LoggerInterface $logger
    ) {
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->logger = $logger;

        $this->eventBridgeClient = new EventBridgeClient([
            'version' => '2015-10-07',
            'region' => 'ap-southeast-2',
            'credentials' => [
                'key' => '',
                'secret' => ''
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function notify(WebhookInterface $webhook, array $data): NotifierResult
    {
        $notifierResult = new NotifierResult();
        $notifierResult->setSubscriptionId($webhook->getSubscriptionId());

        // Elaborate event parameters
        $eventEntry = [
             // TODO: Replace with store domain
            'Source' => 'example.com',
            'Detail' => $this->json->serialize($data),
            // TODO: Format event name
            'DetailType' => $webhook->getEventName(),
            'Resources' => [],
            'Time' => time()
        ];

        try {
            $eventEntry['EventBusName'] = 'default';
            $result = $this->eventBridgeClient->putEvents([
                'Entries' => [$eventEntry]
            ]);

            $notifierResult->setSuccess(true);

            $notifierResult->setResponseData(
                 $this->json->serialize($result)
            );

        } catch (\Exception $exception) {
            $this->logger->error($exception);

            $notifierResult->setSuccess(false);

            $notifierResult->setResponseData(
                $this->json->serialize($exception)
            );
        }


        return $notifierResult;
    }
}
