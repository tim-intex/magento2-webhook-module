<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Webhook\Webhook\Model;

use Webhook\Webhook\Api\Data\WebhookEventInterface;
use Magento\Framework\DataObject;

/**
 * Webhook Event model
 */
class WebhookEvent extends DataObject implements WebhookEventInterface
{
    /**
     * Get event type
     *
     * @return string
     */
    public function getEventType()
    {
        return $this->getData(self::EVENT_TYPE);
    }

    /**
     * Set event type
     *
     * @param string $eventType
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setEventType($eventType)
    {
        return $this->setData(self::EVENT_TYPE, $eventType);
    }

    /**
     * Get event name
     *
     * @return string
     */
    public function getEventName()
    {
        return $this->getData(self::EVENT_NAME);
    }

    /**
     * Set event name
     *
     * @param string $eventName
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setEventName($eventName)
    {
        return $this->setData(self::EVENT_NAME, $eventName);
    }

    /**
     * Get event data
     *
     * @return array
     */
    public function getData()
    {
        return $this->getData(self::DATA);
    }

    /**
     * Set event data
     *
     * @param array $data
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setData($data)
    {
        return $this->setData(self::DATA, $data);
    }

    /**
     * Get created at timestamp
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return \Webhook\Webhook\Api\Data\WebhookEventInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}