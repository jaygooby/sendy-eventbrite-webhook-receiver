# sendy-eventbrite-webhook-receiver
A simple webhook receiver that updates a custom field in your Sendy list with a custom value, whenever an order is placed via Eventbrite. Removes the need for a Zapier or other third-party integration.

## Prerequisites

 1. A Sendy installation
 2. A Sendy list with a custom field you want updating with a value, when an Eventbrite order is made (e.g. ticket-bought=1)
 3. A Sendy API key
 4. This file saved somewhere under your Sendy installation, e.g. `/path/to/sendy/webhooks/eventbrite.php`
 5. An Eventbrite account
 6. An [Eventbrite webhook](https://www.eventbrite.co.uk/myaccount/webhooks/) configured with an `order.placed` action and the URL to this file in your Sendy installation, e.g. http://sendy.example.com/webhooks/eventbrite.php
 7. An [Eventbrite App](https://www.eventbrite.co.uk/myaccount/apps/), so you can access your personal oauth2 token
