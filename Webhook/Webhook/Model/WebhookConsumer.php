<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Webhook\Webhook\Model;

use Webhook\Webhook\Api\Data\WebhookEventInterface;
use Webhook\Webhook\Helper\DataMap;
use Psr\Log\LoggerInterface;

/**
 * Webhook Consumer for processing webhook events from queue
 */
class WebhookConsumer
{
    /**
     * @var DataMap
     */
    private $dataMap;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize dependencies
     *
     * @param DataMap $dataMap
     * @param LoggerInterface $logger
     */
    public function __construct(
        DataMap $dataMap,
        LoggerInterface $logger
    ) {
        $this->dataMap = $dataMap;
        $this->logger = $logger;
    }

    /**
     * Process webhook event from queue
     *
     * @param WebhookEventInterface $webhookEvent
     * @return void
     */
    public function process(WebhookEventInterface $webhookEvent)
    {
        try {
            $eventType = $webhookEvent->getEventType();
            $eventName = $webhookEvent->getEventName();
            $data = $webhookEvent->getData();

            $this->logger->info(
                sprintf(
                    'Processing webhook from queue. Type: %s, Name: %s',
                    $eventType,
                    $eventName
                )
            );

            // Extract payload data based on event type
            if ($eventType === 'order') {
                $payload = $this->dataMap->mapOrderPayloadObject($data);
            } elseif ($eventType === 'shipment') {
                $payload = $this->dataMap->mapShipmentPayloadObject($data);
            } else {
                $this->logger->error('Unsupported event type: ' . $eventType);
                return;
            }

            // Send the webhook request
            $result = $this->dataMap->webhookTrackEvent(
                $eventName,
                $payload['customer_identifiers'],
                $payload['customer_properties'],
                $payload['properties'],
                time()
            );

            if ($result === false) {
                $this->logger->error(
                    sprintf(
                        'Webhook request failed for %s event. Entity ID: %s',
                        $eventType,
                        isset($data['entity_id']) ? $data['entity_id'] : 'unknown'
                    )
                );
            } elseif (is_string($result)) {
                $this->logger->warning(
                    sprintf(
                        'Webhook returned message: %s for %s event. Entity ID: %s',
                        $result,
                        $eventType,
                        isset($data['entity_id']) ? $data['entity_id'] : 'unknown'
                    )
                );
            } else {
                $this->logger->info(
                    sprintf(
                        'Webhook event successfully sent. Type: %s, Entity ID: %s',
                        $eventType,
                        isset($data['entity_id']) ? $data['entity_id'] : 'unknown'
                    )
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Error processing webhook from queue: %s',
                    $e->getMessage()
                )
            );
        }
    }
}