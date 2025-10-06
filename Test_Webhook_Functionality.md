# Testing Webhook Functionality

## Manual Testing Steps

### 1. Configuration Testing
1. Install the module in your Magento 2 instance
2. Run `php bin/magento setup:upgrade`
3. Run `php bin/magento cache:flush`
4. Navigate to **Stores > Configuration > Webhook > Webhook Settings**
5. Verify all configuration fields are visible and working
6. Configure with a test webhook endpoint (like webhook.site)
7. Enable both order and shipment webhooks
8. Save configuration

### 2. Order Webhook Testing
1. Place a test order in your Magento store
2. Complete the checkout process
3. Check your webhook endpoint for the received payload
4. Verify the payload contains:
   - Customer information
   - Order details
   - Order items
   - Billing and shipping addresses

### 3. Shipment Webhook Testing
1. Create a shipment for an existing order
   - Navigate to **Sales > Orders**
   - Select an order
   - Click **Ship** button
   - Add tracking information if desired
   - Submit shipment
2. Check your webhook endpoint for the received payload
3. Verify the payload contains:
   - Shipment details (ID, increment ID)
   - Linked order information
   - Shipped items with quantities
   - Tracking numbers and carrier information
   - Customer information
   - Shipping address

### 4. Disable/Enable Testing
1. Disable order webhooks and verify no order events are sent
2. Disable shipment webhooks and verify no shipment events are sent
3. Disable webhooks entirely and verify no events are sent

## Sample Webhook Payloads

### Order Created Payload
```json
{
  "data": {
    "type": "event",
    "attributes": {
      "properties": {
        "value": "100.00",
        "event_id": "123",
        "Discount Value": "0.00",
        "Discount Code": null,
        "BillingAddress": { ... },
        "ShippingAddress": { ... },
        "Items": [
          {
            "ProductId": "1",
            "SKU": "TEST001",
            "ProductName": "Test Product",
            "Quantity": 1,
            "ItemPrice": "100.00"
          }
        ]
      },
      "metric": {
        "data": {
          "type": "metric",
          "attributes": {
            "name": "Placed Order Webhook"
          }
        }
      },
      "profile": {
        "data": {
          "type": "profile",
          "attributes": {
            "properties": {
              "city": "Test City",
              "address1": "123 Test St",
              "zip": "12345",
              "region": "Test State",
              "country": "US"
            },
            "email": "test@example.com",
            "phone_number": "555-1234",
            "first_name": "John",
            "last_name": "Doe"
          }
        }
      },
      "unique_id": "123",
      "time": "1234567890"
    }
  }
}
```

### Shipment Created Payload
```json
{
  "data": {
    "type": "event",
    "attributes": {
      "properties": {
        "ShipmentId": "1",
        "ShipmentIncrementId": "100000001",
        "OrderId": "1",
        "OrderIncrementId": "100000001",
        "ShipmentCreatedAt": "2023-01-01 12:00:00",
        "ShippingMethod": "flatrate_flatrate",
        "Items": [
          {
            "ProductId": "1",
            "SKU": "TEST001",
            "ProductName": "Test Product",
            "Quantity": 1,
            "OrderItemId": "1",
            "ItemPrice": "100.00"
          }
        ],
        "Tracks": [
          {
            "CarrierCode": "ups",
            "Title": "United Parcel Service",
            "TrackNumber": "1Z1234567890123456",
            "CreatedAt": "2023-01-01 12:00:00"
          }
        ],
        "ShippingAddress": { ... }
      },
      "metric": {
        "data": {
          "type": "metric",
          "attributes": {
            "name": "Shipment Created Webhook"
          }
        }
      },
      "profile": {
        "data": {
          "type": "profile",
          "attributes": {
            "properties": {
              "city": "Test City",
              "address1": "123 Test St",
              "zip": "12345",
              "region": "Test State",
              "country": "US"
            },
            "email": "test@example.com",
            "phone_number": "555-1234",
            "first_name": "John",
            "last_name": "Doe"
          }
        }
      },
      "unique_id": null,
      "time": "1234567890"
    }
  }
}
```

## Troubleshooting

### Common Issues

1. **Webhooks not sending**
   - Check if webhooks are enabled in configuration
   - Verify API key is correctly entered
   - Check Magento logs for error messages

2. **Configuration not saving**
   - Ensure proper permissions are set
   - Check if `php bin/magento cache:flush` has been run

3. **Missing shipment data**
   - Verify the shipment has items assigned
   - Check if tracking information is properly added

### Debug Logging
The module logs webhook activities to Magento's system logs:
- Successful webhook sends: "Event Successfully sent to Webhook"
- Failed webhook sends: "Unable to send event to Webhook"

Check logs at: `var/log/system.log`