<?php
  /*
   * Prerequisites:
   *
   * 1. A https://sendy.co installation
   * 2. A Sendy list with a custom field you want updating with a value
   *    when an Eventbrite order is made (e.g. ticket-bought=1)
   * 3. A Sendy API key
   * 4. This file saved somewhere under your Sendy installation, e.g.
   *    /path/to/sendy/webhooks/eventbrite-receiver.php
   * 5. An Eventbrite account
   * 6. An Eventbrite webhook configured with an order.placed action and the URL
   *    to this file in your Sendy installation, e.g.
   *    http://sendy.example.com/webhooks/eventbrite-receiver.php
   * 7. An Eventbrite App, so you can access your personal oauth2 token
   *
   *
   * MIT License
   *
   * Copyright (c) 2018 Jay Caines-Gooby, @jaygooby, jay@gooby.org
   *
   * Permission is hereby granted, free of charge, to any person obtaining a copy
   * of this software and associated documentation files (the "Software"), to deal
   * in the Software without restriction, including without limitation the rights
   * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
   * copies of the Software, and to permit persons to whom the Software is
   * furnished to do so, subject to the following conditions:
   *
   * The above copyright notice and this permission notice shall be included in all
   * copies or substantial portions of the Software.
   *
   * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
   * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
   * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
   * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
   * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
   * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
   * DEALINGS IN THE SOFTWARE.
   */


  // Sendy config - update with your details
  $sendy_list                    = "abc123defghij4567"; // your list id
  $sendy_api_key                 = "XXXXXXXXXXXXXXXXX"; // your API key
  $sendy_url                     = "https://sendy.example.com"; // URL of your Sendy installation

  // Your list's custom field that will be updated with a value once an order
  // is made via Eventbrite
  $sendy_custom_field             = "ticket-bought";
  $sendy_custom_field_value       = 1; // the value the field will receive

  // Eventbrite config - update with your details
  $auth             = "?token=XXXXXXXXXXXXXXXXX"; // your personal Eventbrite oauth2 token
  $extra_params     = "&expand=attendees";

  // Shouldn't need to alter anything below here...
  $sendy_subscribe_url           = $sendy_url . "/subscribe";
  $sendy_subscription_status_url = $sendy_url . "/api/subscribers/subscription-status.php";
  $sendy_subscribed_status       = "Subscribed";
  $sendy_plain_text_response     = "true";

  $eventbrite_params             = $auth . $extra_params;
  $eventbrite_payload            = json_decode(file_get_contents('php://input'));
  $eventbrite_webhook_response   = array();
  $eventbrite_order_url          = $payload->{'api_url'} . $params;

  // Get Eventbrite order details
  $order = json_decode(file_get_contents($eventbrite_order_url));

  // Extract all the email addresses, because an order might be for more than
  // one attendee
  $emails = array_column(array_column($order->{"attendees"}, "profile"), "email");

  // Now update each of these Sendy subscribers (assuming they exist as
  // subscribers). Set the $sendy_custom_field to be $sendy_custom_field_value
  // - note that we won't subscribe them if they aren't already subscribed to
  // our $sendy_list
  $sendy_data = array();
  $sendy_results = array();
  $subscribed_emails = array();

  foreach($emails as $email) {
    $sendy_data = ["email"   => $email,
                   "list_id" => $sendy_list,
                   "api_key" => $sendy_api_key];

    // key is always 'http' even if url is https
    $options = array(
      "http" => array(
        "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
        "method"  => "POST",
        "content" => http_build_query($sendy_data),
      ),
    );

    // Query Sendy API to check the subscription status of each email address
    $context = stream_context_create($options);
    $sendy_status = file_get_contents($sendy_subscription_status_url, false, $context);
    $webhook_response[] = $email . ": " . $sendy_status;
    if ($sendy_status == $sendy_subscribed_status) {
      $subscribed_emails[] = $email;
    }
  }

  // We only want to deal with existing subscribers
  // so loop over the emails in $subscribed_emails - these are the email
  // addresses of people we know are already subscribed to our $sendy_list
  foreach($subscribed_emails as $email) {
    $sendy_data = array("email"             => $email,
                        $sendy_custom_field => $sendy_custom_field_value,
                        "list"              => $sendy_list,
                        "boolean"           => $sendy_plain_text_response);

    // the http key is always 'http' even if the url is an https one
    $options = array(
      "http" => array(
        "header"  => "Content-type: application/x-www-form-urlencoded\r\n",
        "method"  => "POST",
        "content" => http_build_query($sendy_data),
      ),
    );

    // Update each subscribed user's $sendy_custom_field with the value set in
    // $sendy_custom_field_value
    $context  = stream_context_create($options);
    $sendy_status = file_get_contents($sendy_subscribe_url, false, $context);
    $webhook_response[] = "Update " . $email . " was " . $sendy_status;
    $sendy_results[] = $sendy_status;
  }

  header("Content-type:application/json;charset=utf-8");
  echo json_encode(["sendy" => $webhook_response]);
?>
