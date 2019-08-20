<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use OpenEuropa\EnterpriseSearchClient\ApiClientInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IngestionText extends ApiBase {

  /**
   * IngestionText constructor.
   *
   * @param \OpenEuropa\EnterpriseSearchClient\ApiClientInterface $client
   *   The API client.
   * @param string $apiKey
   *   The application key.
   * @param string $database
   *   The datasource database.
   * @param string $uri
   *   The link associated with the document.
   * @param array $parameters
   *   Extra request parameters.
   */
  public function __construct(ApiClientInterface $client, string $apiKey, string $database, string $uri, array $parameters = []) {
    // Force required parameters in the signature itself.
    $parameters = array_merge($parameters, [
      'apiKey' => $apiKey,
      'database' => $database,
      'uri' => $uri,
    ]);

    parent::__construct($client, $parameters);
  }

  /**
   * @inheritDoc
   */
  public function getMethod(): string {
    return 'POST';
  }

  /**
   * @inheritDoc
   */
  public function getUri(): string {
    return 'ingestion/text';
  }

  /**
   * @inheritDoc
   */
  protected function getOptionResolver(): OptionsResolver {
    $resolver = parent::getOptionResolver();

    $resolver->setRequired('apiKey')
      ->setAllowedTypes('apiKey', 'string');

    $resolver->setRequired('database')
      ->setAllowedTypes('database', 'string');

    $resolver->setRequired('uri')
      ->setAllowedTypes('uri', 'string')
      ->setAllowedValues('uri', function ($value) {
        return filter_var($value, FILTER_VALIDATE_URL);
      });

    $resolver->setDefined('text')
      ->setAllowedTypes('text', 'string');

    $resolver->setDefined('language')
      ->setAllowedTypes('languages', 'string[]');
    // @todo Validate languages with ISO 639-1 language codes.
    // $resolver->setAllowedValues('languages', []);

    $resolver->setDefined('metadata');
    // @todo Metadata is a complex structure and it requires its own type.
    // $resolver->setAllowedTypes('metadata', 'array');
    //$resolver->setAllowedValues('metadata', '');

    return $resolver;
  }

}
