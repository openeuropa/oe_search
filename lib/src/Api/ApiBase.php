<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use GuzzleHttp\Psr7\MultipartStream;
use OpenEuropa\EnterpriseSearchClient\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base class for Enterprise Search API requests.
 */
abstract class ApiBase implements ApiInterface {

  /**
   * The API client.
   *
   * @var \OpenEuropa\EnterpriseSearchClient\ClientInterface
   */
  protected $client;

  /**
   * The API base parameters.
   *
   * @todo possibly remove
   *
   * @var array
   */
  protected $parameters;

  /**
   * ApiBase constructor.
   *
   * @param \OpenEuropa\EnterpriseSearchClient\ClientInterface $client
   *   The API client.
   * @param array $parameters
   *   The base parameters common to all API requests. Defaults to empty.
   */
  public function __construct(ClientInterface $client, array $parameters = []) {
    $this->client = $client;
    $this->parameters = $parameters;
  }

  /**
   * Returns the option resolver configured with the API rules.
   *
   * @return \Symfony\Component\OptionsResolver\OptionsResolver $resolver
   *   The options resolver.
   */
  protected function getOptionResolver(): OptionsResolver {
    return new OptionsResolver();
  }

  protected function send(string $method, string $path, array $queryParameters = [], array $formParameters = [], bool $multipart = false) {
    $uri = $this->prepareUri($path, $queryParameters);

    $request = $this->client->getRequestFactory()->createRequest($method, $uri);

    if (!empty($formParameters)) {
      if ($multipart) {
        $stream = $this->getMultipartStream($formParameters);
      }
      else {
        $stream = $this->client->getStreamFactory()->createStream(http_build_query($formParameters));
      }
      $request = $request->withBody($stream);
    }

    //\Drupal::service('http_client')->send($request, ['debug' => TRUE]);
    $this->client->getHttpClient()->sendRequest($request);
  }

  protected function getMultipartStream(array $elements): StreamInterface {
    $parts = [];
    foreach ($elements as $key => $value) {
      $parts[] = [
        'name' => $key,
        'contents' => $value,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
        'filename' => 'blob',
      ];
    }

    return new MultipartStream($parts);
  }

  /**
   * Prepares the URI for a request.
   *
   * @param string $path
   *   The path of the request.
   * @param array $queryParameters
   *   Query parameters. Optional.
   *
   * @return string
   *   The full URI of the request.
   */
  abstract protected function prepareUri(string $path, array $queryParameters = []): string;

}
