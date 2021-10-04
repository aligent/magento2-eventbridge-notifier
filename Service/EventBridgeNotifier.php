<?php

namespace Aligent\EventBridge\Service;

use Aligent\EventBridge\Model\Config as EventBridgeConfig;
use Aligent\Webhooks\Service\Webhook\NotifierInterface;
use Aligent\Webhooks\Api\Data\WebhookInterface;
use Aligent\Webhooks\Helper\NotifierResult;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Aws\EventBridge\EventBridgeClient;
use Psr\Log\LoggerInterface;

/**
 * Class EventBridgeNotifier
 *
 * A notifier for relaying events into AWS EventBridge.
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
     * @var EventBridgeConfig
     */
    private EventBridgeConfig $config;

    /**
     * @var EventBridgeClient
     */
    private $eventBridgeClient;

    public function __construct(
        Json $json,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        EventBridgeConfig $config
    ) {
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->config = $config;

        $this->eventBridgeClient = new EventBridgeClient([
            'version' => '2015-10-07',
            'region' => $this->config->getAWSRegion(),
            'credentials' => [
                'key' => $this->config->getAWSKeyId(),
                'secret' => $this->encryptor->decrypt($this->config->getAWSSecretKey())
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

        try {
            $eventEntry['EventBusName'] = $this->config->getEventBridgeBus();
            $result = $this->eventBridgeClient->putEvents([
                 'Entries' => [
                      [
                           'Source' => $this->config->getEventBridgeSource(),
                           'Detail' => $this->json->serialize($data),
                           'DetailType' => $webhook->getEventName(),
                           'Resources' => [],
                           'Time' => time()
                      ]
                 ]
            ]);

            // Some event failures, which don't fail the entire request but do cause a failure of the
            // event submission result in failed results in the result->entries.
            // https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-eventbridge-2015-10-07.html#putevents
            if ( isset($result['FailedEntryCount']) && $result['FailedEntryCount'] > 0) {
                 $notifierResult->setSuccess(false);

                 // As we are only ever submitting one event at a time, assume that only one result
                 // can be returned.
                 $entry = $result['Entries'][0];
                 if ($entry !== null) {
                      $notifierResult->setResponseData(
                           $this->json->serialize($entry)
                      );
                 }

            } else {
                 $notifierResult->setSuccess(true);

                 $notifierResult->setResponseData(
                      $this->json->serialize($result)
                 );
            }
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
