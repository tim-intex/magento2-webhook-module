<?php

namespace Webhook\Webhook\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class DataMap extends AbstractHelper
{
    
    const USER_AGENT = 'Webhook/1.0';
    
    protected $scopeConfig;
    protected $_logger;
    public function __construct(
        Context $context,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->_logger = $logger;
        
        // DEBUG: Log when DataMap is instantiated
        $this->_logger->debug('DI_COMPILE_DEBUG: DataMap helper instantiated - This might be triggering API key validation during di:compile');
    }

    /**
     * Generic method to send webhook events
     * @param string $eventType
     * @param mixed $dataObject
     * @return bool|string
     */
    public function sendWebhookEvent($eventType, $dataObject)
    {
        if (!$this->isEnabled()) {
            return 'Webhooks are disabled';
        }
        
        if ($eventType === 'order') {
            if (!$this->isOrderEnabled()) {
                return 'Order webhook is disabled';
            }
            $eventName = $this->getOrderEventName();
            $payload = $this->mapOrderPayloadObject($dataObject);
        } elseif ($eventType === 'shipment') {
            if (!$this->isShipmentEnabled()) {
                return 'Shipment webhook is disabled';
            }
            $eventName = $this->getShipmentEventName();
            $payload = $this->mapShipmentPayloadObject($dataObject);
        } else {
            return 'Unsupported event type';
        }
        
        return $this->webhookTrackEvent($eventName, $payload['customer_identifiers'], $payload['customer_properties'], $payload['properties'], time());
    }

    /**
     * Legacy method for backward compatibility
     * @param string $order
     * @return bool|string
     */
    public function sendOrderToWebhook($order)
    {
        return $this->sendWebhookEvent('order', $order);
    }

    /**
     * Helper function that takes the order object and returns a mapped out array
     * @return array
     */
    public function mapOrderPayloadObject($order)
    {
        $customer_identifiers = [];
        $customer_properties = [];
        $properties = [];
        $items = [];

        $shipping = $order->getShippingAddress();
        $billing = $order->getBillingAddress();

        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'ProductId' => $item->getProductId() ? $item->getProductId() : '',
                'SKU' => $item->getSku() ? $item->getSku() : '',
                'ProductName' => $item->getName() ? $item->getName() : '',
                'Quanitity' => $item->getQtyOrdered() ? (int)$item->getQtyOrdered() : '',
                'ItemPrice' => $item->getPrice() ? (float)$item->getPrice() : ''
            ];
        }

        if ($order->getCustomerEmail()) $customer_identifiers['email'] = $order->getCustomerEmail();
        if ($order->getCustomerName()) $customer_identifiers['first_name'] = $order->getCustomerFirstName();
        if ($order->getCustomerLastname()) $customer_identifiers['last_name'] = $order->getCustomerLastname();
        if ($shipping->getTelephone()) $customer_identifiers['phone_number'] = $shipping->getTelephone();

        if ($shipping->getCity()) $customer_properties['city'] = $shipping->getCity();
        if ($shipping->getData('street')) $customer_properties['address1'] = $shipping->getData('street');
        if ($shipping->getPostcode()) $customer_properties['zip'] = $shipping->getPostcode();
        if ($shipping->getRegion()) $customer_properties['region'] = $shipping->getRegion();
        if ($shipping->getCountryId()) $customer_properties['country'] = $shipping->getCountryId();

        if ($order->getGrandTotal()) $properties['value'] = (float)$order->getGrandTotal();
        if ($order->getQuoteId()) $properties['event_id'] = $order->getQuoteId();
        if ($order->getDiscountAmount()) $properties['Discount Value'] = (float)$order->getDiscountAmount();
        if ($order->getCouponCode()) $properties['Discount Code'] = $order->getCouponCode();

        $properties['BillingAddress'] = $this->mapAddress($billing);
        $properties['ShippingAddress'] = $this->mapAddress($shipping);
        $properties['Items'] = $items;

        return ['customer_identifiers' => $customer_identifiers, 'customer_properties' => $customer_properties, 'properties' => $properties];
    }

    /**
     * Helper function that takes the shipment object and returns a mapped out array
     * @return array
     */
    public function mapShipmentPayloadObject($shipment)
    {
        $customer_identifiers = [];
        $customer_properties = [];
        $properties = [];
        $items = [];
        $tracks = [];

        $order = $shipment->getOrder();
        $shippingAddress = $order->getShippingAddress();

        // Map shipment items
        foreach ($shipment->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            $items[] = [
                'ProductId' => $item->getProductId() ? $item->getProductId() : '',
                'SKU' => $item->getSku() ? $item->getSku() : '',
                'ProductName' => $item->getName() ? $item->getName() : '',
                'Quantity' => $item->getQty() ? (int)$item->getQty() : '',
                'OrderItemId' => $orderItem->getId() ? $orderItem->getId() : '',
                'ItemPrice' => $orderItem->getPrice() ? (float)$orderItem->getPrice() : ''
            ];
        }

        // Map tracking information
        foreach ($shipment->getAllTracks() as $track) {
            $tracks[] = [
                'CarrierCode' => $track->getCarrierCode() ? $track->getCarrierCode() : '',
                'Title' => $track->getTitle() ? $track->getTitle() : '',
                'TrackNumber' => $track->getTrackNumber() ? $track->getTrackNumber() : '',
                'CreatedAt' => $track->getCreatedAt() ? $track->getCreatedAt() : ''
            ];
        }

        // Customer identifiers
        if ($order->getCustomerEmail()) $customer_identifiers['email'] = $order->getCustomerEmail();
        if ($order->getCustomerFirstname()) $customer_identifiers['first_name'] = $order->getCustomerFirstname();
        if ($order->getCustomerLastname()) $customer_identifiers['last_name'] = $order->getCustomerLastname();
        if ($shippingAddress && $shippingAddress->getTelephone()) $customer_identifiers['phone_number'] = $shippingAddress->getTelephone();

        // Customer properties
        if ($shippingAddress) {
            if ($shippingAddress->getCity()) $customer_properties['city'] = $shippingAddress->getCity();
            if ($shippingAddress->getData('street')) $customer_properties['address1'] = $shippingAddress->getData('street');
            if ($shippingAddress->getPostcode()) $customer_properties['zip'] = $shippingAddress->getPostcode();
            if ($shippingAddress->getRegion()) $customer_properties['region'] = $shippingAddress->getRegion();
            if ($shippingAddress->getCountryId()) $customer_properties['country'] = $shippingAddress->getCountryId();
        }

        // Shipment properties
        $properties['ShipmentId'] = $shipment->getId() ? $shipment->getId() : '';
        $properties['ShipmentIncrementId'] = $shipment->getIncrementId() ? $shipment->getIncrementId() : '';
        $properties['OrderId'] = $order->getId() ? $order->getId() : '';
        $properties['OrderIncrementId'] = $order->getIncrementId() ? $order->getIncrementId() : '';
        $properties['ShipmentCreatedAt'] = $shipment->getCreatedAt() ? $shipment->getCreatedAt() : '';
        $properties['ShippingMethod'] = $order->getShippingMethod() ? $order->getShippingMethod() : '';
        $properties['Items'] = $items;
        $properties['Tracks'] = $tracks;
        
        if ($shippingAddress) {
            $properties['ShippingAddress'] = $this->mapAddress($shippingAddress);
        }

        return ['customer_identifiers' => $customer_identifiers, 'customer_properties' => $customer_properties, 'properties' => $properties];
    }

    /**
     * Helper function that takes the address_type object and returns a mapped out array
     * @return array
     */
    private function mapAddress($address_type)
    {
        $address = [];
        if ($address_type->getFirstname()) $address['FirstName'] = $address_type->getFirstname();
        if ($address_type->getLastname()) $address['LastName'] = $address_type->getLastname();
        if ($address_type->getCompany()) $address['Company'] = $address_type->getCompany();
        if ($address_type->getData('street')) $address['Address1'] = $address_type->getData('street');
        if ($address_type->getCity()) $address['City'] = $address_type->getCity();
        if ($address_type->getRegion()) $address['Region'] = $address_type->getRegion();
        if ($address_type->getRegionCode()) $address['RegionCode'] = $address_type->getRegionCode();
        if ($address_type->getCountryId()) $address['CountryCode'] = $address_type->getCountryId();
        if ($address_type->getPostCode()) $address['Zip'] = $address_type->getPostCode();
        if ($address_type->getTelephone()) $address['Phone'] = $address_type->getTelephone();

        return $address;
    }

    public function webhookTrackEvent($event, $customer_identifiers = array(), $customer_properties = array(), $properties = array(), $timestamp = NULL)
    {
        if ((!array_key_exists('email', $customer_identifiers) || empty($customer_identifiers['email']))
            && (!array_key_exists('phone', $customer_identifiers) || empty($customer_identifiers['phone']))
        ) {
            return 'You must identify a user by email or phone number.';
        }

        $body = [
          "data" => [
            "type" => "event",
            "attributes" => [
              "properties" => $properties,
              "metric" => [
                "data" => [
                  "type" => "metric",
                  "attributes" => [
                    "name" => $event
                  ]
                ]
              ],
              "profile" => [
                "data" => [
                  "type" => "profile",
                  "attributes" => [
                    "properties" => $customer_properties,
                    "email" => "{$customer_identifiers['email']}",
                    "phone_number" => "{$customer_identifiers['phone']}",
                    "first_name" => "{$customer_identifiers['first_name']}",
                    "last_name" => "{$customer_identifiers['last_name']}"
                  ]
                ]
              ],
              "value" => "{$properties['value']}",
              "unique_id" => "{$properties['event_id']}",
              "time" => "{$timestamp}"
            ]
          ]
        ];

        return $this->make_request("api/events/", json_encode($body));
    }

    protected function make_request($path, $body)
    {
        $url = $this->getHost() . $path;
        
        // DEBUG: Log the request details
        $this->_logger->info('Webhook Request - URL: ' . $url);
        $this->_logger->info('Webhook Request - Body: ' . $body);
        $this->_logger->info('Webhook Request - API Key: ' . substr($this->getApiKey(), 0, 8) . '...');
        
        $options = [
            'http' => [
                'header' => "content-type: application/json\r\n" .
                           "accept: application/json\r\n" .
                           "Authorization: Webhook-API-Key " . $this->getApiKey() . "\r\n" .
                           "revision: " . $this->getRevision() . "\r\n" .
                           "User-Agent: " . self::USER_AGENT . "\r\n",
                'method' => 'POST',
                'content' => $body,
            ],
        ];

        $context = stream_context_create($options);
        
        // Add error handling for the request
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            $this->_logger->error('Webhook Request Failed - Error: ' . ($error['message'] ?? 'Unknown error'));
            return false;
        }
        
        // DEBUG: Log response details
        $this->_logger->info('Webhook Response - Status: ' . (isset($http_response_header) ? $http_response_header[0] : 'No status'));
        $this->_logger->info('Webhook Response - Body: ' . $response);
        
        return $response;
    }

    /**
     * Configuration helper methods
     */
    protected function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'webhook_settings/general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function getApiKey()
    {
        // DEBUG: Log when API key is being accessed
        $this->_logger->debug('DI_COMPILE_DEBUG: getApiKey() method called - This might be causing the API key requirement during di:compile');
        
        $apiKey = $this->scopeConfig->getValue(
            'webhook_settings/general/api_key',
            ScopeInterface::SCOPE_STORE
        );
        
        // DEBUG: Log the API key status
        $this->_logger->debug('DI_COMPILE_DEBUG: API Key value is ' . (empty($apiKey) ? 'EMPTY' : 'SET'));
        
        return $apiKey;
    }

    protected function getHost()
    {
        // DEBUG: Log when host is being accessed
        $this->_logger->debug('DI_COMPILE_DEBUG: getHost() method called - Current host: ' . $this->scopeConfig->getValue(
            'webhook_settings/general/host',
            ScopeInterface::SCOPE_STORE
        ));
        
        return $this->scopeConfig->getValue(
            'webhook_settings/general/host',
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function getRevision()
    {
        return $this->scopeConfig->getValue(
            'webhook_settings/general/revision',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrderEventName()
    {
        return $this->scopeConfig->getValue(
            'webhook_settings/events/order_event_name',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getShipmentEventName()
    {
        return $this->scopeConfig->getValue(
            'webhook_settings/events/shipment_event_name',
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function isOrderEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'webhook_settings/events/order_enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    protected function isShipmentEnabled()
    {
        return $this->scopeConfig->isSetFlag(
            'webhook_settings/events/shipment_enabled',
            ScopeInterface::SCOPE_STORE
        );
    }
}