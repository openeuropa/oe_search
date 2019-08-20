<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use OpenEuropa\EnterpriseSearchClient\Model\Ingestion;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IngestionApi extends ApiBase {

  public function ingestText(array $parameters): Ingestion {
    $resolver = $this->getOptionResolver();

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
    // $resolver->setAllowedValues('metadata', '');

    $parameters = $resolver->resolve($parameters);

    // Build the request.
    // Parse response.
    $ingestion = new Ingestion();

    return $ingestion;
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

    return $resolver;
  }

}
