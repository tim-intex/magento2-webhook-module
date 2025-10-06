<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Webhook\Webhook\Api\Data;

/**
 * Interface for Webhook Event data
 */
interface WebhookEventInterface
{
    /**#@+
     * Constants for keys of data array
     */
    const EVENT_TYPE = 'event_type';
    const EVENT_NAME = 'event_name';
    const DATA = 'data';
    const CREATED_AT = 'created_at';
    /**#@-*/

    /**
     * Get event type
     *
     * @return string
     */
    public function getEventType();

    /**
     * Set event type
     *
     * @param string $eventType
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setEventType($eventType);

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName();

    /**
     * Set event name
     *
     * @param string $eventName
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setEventName($eventName);

    /**
     * Get event data
     *
     * @return array
     */
    public function getData();

    /**
     * Set event data
     *
     * @param array $data
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setData($data);

    /**
     * Get created at timestamp
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setCreatedAt($createdAt);
}