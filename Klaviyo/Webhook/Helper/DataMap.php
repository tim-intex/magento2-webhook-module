<?php

namespace Klaviyo\Webhook\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class DataMap extends AbstractHelper
{

    const KLAVIYO_API_KEY = "PUBLIC_API_KEY_GOES_HERE";
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const PLACED_ORDER = 'Placed Order Webhook';
    /**
     * @param string $order
     * @return bool|string
     */

    public function sendOrderToKlaviyo($order)
    {
        $payload = $this->mapPayloadObject($order);
        return $this->klaviyoTrackEvent(self::PLACED_ORDER, $payload['customer_identifiers'], $payload['customer_properties'], $payload['properties'], time());
    }

    /**
     * Helper function that takes the order object and returns a mapped out array
     * @return array
     */
    private function mapPayloadObject($order)
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

    public function klaviyoTrackEvent($event, $customer_identifiers = array(), $customer_properties = array(), $properties = array(), $timestamp = NULL)
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

        return $this->make_request("client/events/", json_encode($body));
    }

    protected function make_request($path, $body)
    {
        $options = [
            'http' => [
                'header' => "content-type: application/json",
                'header' => "accept: application/json",
                'header' => "revision: 2024-07-15",
                'header' => "User-Agent: {$USER_AGENT}",
                'method' => 'POST',
                'content' => {"data": $body},
            ],
        ];

        $context = stream_context_create($options);
        $url = self::KLAVIYO_HOST . $path . "?company_id=" . self::KLAVIYO_API_KEY;
        $response = file_get_contents($url, false, $context);
    }
}
