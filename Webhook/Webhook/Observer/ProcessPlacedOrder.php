<?php

namespace Webhook\Webhook\Observer;

use Webhook\Webhook\Model\WebhookPublisher;
use Webhook\Webhook\Helper\DataMap;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProcessPlacedOrder implements ObserverInterface
{

    private $logger;
    protected $dataHelper;
    protected $webhookPublisher;

    public function __construct(
        DataMap $dataHelper,
        WebhookPublisher $webhookPublisher,
        LoggerInterface $logger
    ) {
        $this->dataHelper = $dataHelper;
        $this->webhookPublisher = $webhookPublisher;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $this->logger->info('Publishing order webhook to queue for Order ID: ' . $order->getId());
        
        try {
            // Get event name from configuration
            $eventName = $this->dataHelper->getOrderEventName();
            
            // Publish to queue instead of sending directly
            $this->webhookPublisher->publish('order', $eventName, $order->getData());
            
            $this->logger->info('Order webhook successfully published to queue for Order ID: ' . $order->getId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish order webhook for Order ID: ' . $order->getId() . '. Error: ' . $e->getMessage());
        }
    }
}