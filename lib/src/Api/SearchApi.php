<?php

declare(strict_types = 1);

namespace OpenEuropa\EnterpriseSearchClient\Api;

use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchApi extends ApiBase {

  public function search(array $parameters = []) {
    $resolver = $this->getOptionResolver();

    $resolver->setRequired('text')
      ->setAllowedTypes('text', 'string')
      ->setDefault('text', '***');

    $parameters = $resolver->resolve($parameters);

    $queryKeys = array_flip(['apiKey', 'text']);
    $queryParameters = array_intersect_key($parameters, $queryKeys);
    $bodyParameters = array_diff_key($parameters, $queryKeys);
    $response = $this->send('POST', 'rest/search', $queryParameters, $bodyParameters, true);
  }

  /**
   * @inheritDoc
   */
  protected function getOptionResolver(): OptionsResolver {
    $resolver = parent::getOptionResolver();

    $resolver->setRequired('apiKey')
      ->setAllowedTypes('apiKey', 'string')
      ->setDefault('apiKey', $this->client->getConfiguration('apiKey'));

    return $resolver;
  }

  /**
   * @inheritDoc
   */
  protected function prepareUri(string $path, array $queryParameters = []): string {
    $base_path = $this->client->getConfiguration('search_api_endpoint');
    $uri = rtrim($base_path, '/') . '/' . ltrim($path, '/');

    return $this->addQueryParameters($uri, $queryParameters);
  }

}
