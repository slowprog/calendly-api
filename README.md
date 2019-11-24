# Calendly API wrapper

A simple PHP library for Calendly API. The API's main documentation is available through the [API portal](https://developer.calendly.com/docs/getting-started).

## Installation

To install use composer:

```bash
composer require slowprog/calendly-api
```

## Usage

```php
<?php

require 'vendor/autoload.php';

use Calendly\CalendlyApi;
use Calendly\CalendlyApiException;

$calendlyApi = new CalendlyApi('YOUR_API_KEY');

try {
    $calendlyApi->echo();
    
    $webhook = $calendlyApi->createWebhook('https://some.site', [
        CalendlyApi::EVENT_CREATED, 
        CalendlyApi::EVENT_CANCELED,
    ]);
    
    var_dump($webhook['id']);
    
    $webhook = $calendlyApi->getWebhook($webhook['id']);
    
    var_dump($webhook['data'][0]['attributes']['created_at']);
    
    $webhooks = $calendlyApi->getWebhooks();
    
    var_dump(count($webhook['data']));
    
    $calendlyApi->deleteWebhook($webhook['data'][0]['id']);    
} catch (CalendlyApiException $e) {
    var_dump($e->getMessage());
}
```