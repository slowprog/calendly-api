<?php

namespace Calendly;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Log;
use GuzzleHttp\Client;

class CalendlyApi
{
    /**
     * @const string
     */
    public const EVENT_CREATED = 'invitee.created';

    /**
     * @const string
     */
    public const EVENT_CANCELED = 'invitee.canceled';

    /**
     * @const string
     */
    private const METHOD_GET = 'get';

    /**
     * @const string
     */
    private const METHOD_POST = 'post';

    /**
     * @const string
     */
    private const METHOD_DELETE = 'delete';

    /**
     * @const string
     */
    private const API_URL = 'https://calendly.com';

    /**
     * @var Client
     */
    private $client;

    /**
     * @param string      $apiKey
     * @param Client|null $client
     */
    public function __construct($apiKey, Client $client = null)
    {
        $this->client = $client ?? new Client([
                'base_uri' => self::API_URL,
                'headers'  => [
                    'X-TOKEN' => $apiKey
                ],
            ]);
    }

    /**
     * Test authentication token.
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function echo(): array
    {
        return $this->callApi(self::METHOD_GET, 'echo');
    }

    /**
     * Create a webhook subscription.
     *
     * @param string $url
     * @param array  $events
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function createWebhook($url, $events): array
    {
        if (array_diff($events, [self::EVENT_CREATED, self::EVENT_CANCELED])) {
            throw new CalendlyApiException('The specified event types do not exist');
        }

        return $this->callApi(self::METHOD_POST, 'hooks', [
            'url'    => $url,
            'events' => $events,
        ]);
    }

    /**
     * Get a webhook subscription by ID.
     *
     * @param int $id
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function getWebhook($id): array
    {
        return $this->callApi(self::METHOD_GET, 'hooks/' . $id);
    }

    /**
     * Get list of a webhooks subscription.
     *
     * @return array
     *
     * @throws CalendlyApiException
     */
    public function getWebhooks(): array
    {
        return $this->callApi(self::METHOD_GET, 'hooks');
    }

    /**
     * Delete a webhook subscription.
     *
     * @return void
     *
     * @throws CalendlyApiException
     */
    public function deleteWebhook($id): void
    {
        try {
            $this->callApi(self::METHOD_DELETE, 'hooks/' . $id);
        } catch (CalendlyApiException $e) {
            if ($e->getCode() != 404) {
                throw $e;
            }
        }
    }

    /**
     * @param string $method
     * @param string $endpoint
     * @param array  $params
     *
     * @return array|null
     *
     * @throws CalendlyApiException
     */
    private function callApi($method, $endpoint, array $params = [])
    {
        $url = sprintf('/api/v1/%s', $endpoint);

        $data = [
            'query' => $params,
        ];

        if ($method != self::METHOD_GET) {
            $data = [
                'form_params' => $params,
            ];
        }

        try {
            try {
                $response = $this->client->request($method, $url, $data);
            } catch (GuzzleException $e) {
                if ($e instanceof ClientException) {
                    $response = $e->getResponse();
                    $message  = (string)$response->getBody();
                    $headers  = $response->getHeader('content-type');

                    if (count($headers) && strpos($headers[0], 'application/json') === 0) {
                        $message = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                        $message = $message['message'];
                    }

                    throw new CalendlyApiException($message, $response->getStatusCode());
                } else {
                    throw new CalendlyApiException('Failed to get Calendly data: ' . $e->getMessage(), $e->getCode());
                }
            }

            $headers = $response->getHeader('content-type');

            if (count($headers) && strpos($headers[0], 'application/json') === 0) {
                $response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $e) {
            throw new CalendlyApiException('Invalid JSON: ' . $e->getMessage(), 500);
        }

        return $response;
    }
}
