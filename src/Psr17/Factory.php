<?php

declare(strict_types = 1);

namespace Drupal\oe_search\Psr17;

use Laminas\Diactoros\Request;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

use function fopen;
use function fwrite;
use function rewind;

/**
 * Class implementing factory methods for Request, Response, Stream and Uri.
 *
 * @todo Remove this one support for drupal 8.9 is dropped.
 * @final This is a value object class, no need to be extended or mocked.
 */
final class Factory implements RequestFactoryInterface, ResponseFactoryInterface, StreamFactoryInterface, UriFactoryInterface {

  /**
   * {@inheritDoc}
   */
  public function createRequest(string $method, $uri) : RequestInterface {
    return new Request($uri, $method);
  }

  /**
   * {@inheritDoc}
   */
  public function createResponse(int $code = 200, string $reasonPhrase = '') : ResponseInterface {
    return (new Response())
      ->withStatus($code, $reasonPhrase);
  }

  /**
   * {@inheritDoc}
   */
  public function createStream(string $content = '') : StreamInterface {
    $resource = fopen('php://temp', 'r+');
    fwrite($resource, $content);
    rewind($resource);

    return $this->createStreamFromResource($resource);
  }

  /**
   * {@inheritDoc}
   */
  public function createStreamFromFile(string $file, string $mode = 'r') : StreamInterface {
    return new Stream($file, $mode);
  }

  /**
   * {@inheritDoc}
   */
  public function createStreamFromResource($resource) : StreamInterface {
    return new Stream($resource);
  }

  /**
   * {@inheritDoc}
   */
  public function createUri(string $uri = '') : UriInterface {
    return new Uri($uri);
  }

}
