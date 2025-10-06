<?php

namespace Webhook\Webhook\Observer;

use Webhook\Webhook\Helper\DataMap;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use \Psr\Log\LoggerInterface;

class ProcessPlacedOrder implements ObserverInterface
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
        $order = $observer->getEvent()->getOrder();
        $result = $this->dataHelper->sendOrderToWebhook($order);
        if (!$result) {
            $this->logger->info('Unable to send event to Webhook');
        }
        $this->logger->info('Event Successfully sent to Webhook');
    }
}