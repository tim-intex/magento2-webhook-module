<?php

namespace Webhook\Webhook\Observer;

use Webhook\Webhook\Helper\DataMap;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Psr\Log\LoggerInterface;

class ProcessShipment implements ObserverInterface
{

    private $logger;
    protected $dataHelper;

    public function __construct(DataMap $dataHelper, LoggerInterface $logger)
    {
        $this->dataHelper = $dataHelper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();
        $result = $this->dataHelper->sendWebhookEvent('shipment', $shipment);
        if (!$result) {
            $this->logger->info('Unable to send shipment event to Webhook');
        }
        $this->logger->info('Shipment event Successfully sent to Webhook');
    }
}