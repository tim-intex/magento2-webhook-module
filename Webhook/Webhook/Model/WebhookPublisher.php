<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Webhook\Webhook\Model;

use Magento\Framework\MessageQueue\PublisherInterface;
use Webhook\Webhook\Api\Data\WebhookEventInterface;
use Psr\Log\LoggerInterface;

/**
 * Webhook Publisher for message queue
 */
class WebhookPublisher
{
    /**
     * Queue topic name
     */
    const TOPIC_NAME = 'webhook.event.queue';

    /**
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Initialize dependencies
     *
     * @param PublisherInterface $publisher
     * @param LoggerInterface $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        LoggerInterface $logger
    ) {
        $this->publisher = $publisher;
        $this->logger = $logger;
    }

    /**
     * Publish webhook event to queue
     *
     * @param string $eventType
     * @param string $eventName
     * @param array $data
     * @return void
     */
    public function publish($eventType, $eventName, $data)
    {
        try {
            /** @var WebhookEventInterface $webhookEvent */
            $webhookEvent = new WebhookEvent();
            $webhookEvent->setEventType($eventType);
            $webhookEvent->setEventName($eventName);
            $webhookEvent->setData($data);
            $webhookEvent->setCreatedAt(date('Y-m-d H:i:s'));

            $this->publisher->publish(self::TOPIC_NAME, $webhookEvent);
            
            $this->logger->info(
                sprintf(
                    'Webhook event published to queue. Type: %s, Name: %s, ID: %s',
                    $eventType,
                    $eventName,
                    isset($data['entity_id']) ? $data['entity_id'] : 'unknown'
                )
            );
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf(
                    'Failed to publish webhook event to queue. Error: %s',
                    $e->getMessage()
                )
            );
        }
    }
}