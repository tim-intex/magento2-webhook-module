<?php

namespace Webhook\Webhook\Observer;

use Webhook\Webhook\Model\WebhookPublisher;
use Webhook\Webhook\Helper\DataMap;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class ProcessShipment implements ObserverInterface
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
        $shipment = $observer->getEvent()->getShipment();
        $this->logger->info('Publishing shipment webhook to queue for Shipment ID: ' . $shipment->getId());
        
        try {
            // Get event name from configuration
            $eventName = $this->dataHelper->getShipmentEventName();
            
            // Publish to queue instead of sending directly
            $this->webhookPublisher->publish('shipment', $eventName, $shipment->getData());
            
            $this->logger->info('Shipment webhook successfully published to queue for Shipment ID: ' . $shipment->getId());
        } catch (\Exception $e) {
            $this->logger->error('Failed to publish shipment webhook for Shipment ID: ' . $shipment->getId() . '. Error: ' . $e->getMessage());
        }
    }
}