<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use GuzzleHttp\Psr7\MultipartStream;
use OpenEuropa\EnterpriseSearchClient\ClientInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

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
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

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

  /**
   * Returns the option resolver configured with the API rules.
   *
   * @return \Symfony\Component\OptionsResolver\OptionsResolver $resolver
   *   The options resolver.
   */
  protected function getOptionResolver(): OptionsResolver {
    return new OptionsResolver();
  }

  /**
   * Sends a request and return its response.
   *
   * @param string $method
   *   The request method.
   * @param string $path
   *   The request relative path.
   * @param array $queryParameters
   *   The query parameters.
   * @param array $formParameters
   *   The parameters to send as body of the request.
   * @param bool $multipart
   *   If the request is a multipart one. Useful only for POST requests.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @throws \Psr\Http\Client\ClientExceptionInterface
   *   Thrown if an error happened while processing the request.
   */
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

    $response = $this->client->getHttpClient()->sendRequest($request);

    return $response;
  }

  /**
   * Creates a multipart stream from a list of elements.
   *
   * @param array $elements
   *   The various elements of the stream.
   *
   * @return \Psr\Http\Message\StreamInterface
   *   The multipart stream.
   */
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
   * Returns a configured serializer to convert API responses.
   *
   * @return \Symfony\Component\Serializer\SerializerInterface
   *   The serializer.
   */
  protected function getSerializer(): SerializerInterface {
    if ($this->serializer === NULL) {
      $this->serializer = new Serializer([
        new ObjectNormalizer(),
      ], [
        new JsonEncoder(),
      ]);
    }

    return $this->serializer;
  }

  /**
   * Adds query parameters to a uri.
   *
   * @todo use \Psr\Http\Message\UriInterface to handle URIs.
   *
   * @param string $uri
   *   The URI.
   * @param array $queryParameters
   *   A key-value list of query parameters.
   *
   * @return string
   *   The processed URI.
   */
  protected function addQueryParameters(string $uri, array $queryParameters): string {
    if (!empty($queryParameters)) {
      $query = http_build_query($queryParameters);
      $glue = strpos($uri, '?') === false ? '?' : '&';
      $uri .= $glue . $query;
    }

    return $uri;
  }

}
