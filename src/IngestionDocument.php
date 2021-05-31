<?php

declare(strict_types=1);

namespace Drupal\oe_search;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Class for holding and preparing data needed for ingestion of one item.
 */
class IngestionDocument {

  // @TODO: add document base in client and extend that one.

  /**
   * The document unique reference.
   *
   * @var string
   */
  protected $reference;

  /**
   * The document url.
   *
   * @var string
   */
  protected $url;

  /**
   * The document metadata.
   *
   * It consists of a nested array where the first level contains the field name
   * as key and the value is a list of values for the field itself.
   *
   * @var array
   */
  protected $metadata = [];

  /**
   * The document content.
   *
   * @var string
   */
  protected $content;

  /**
   * The document languages.
   *
   * @var string[]
   */
  protected $languages;

  /**
   * Is the document eligible.
   *
   * @var bool
   */
  protected $status;

  /**
   * Returns the document unique reference.
   *
   * @return string
   *   The document unique reference.
   */
  public function getReference(): string {
    return $this->reference;
  }

  /**
   * Sets the document unique reference.
   *
   * @param string $reference
   *   The document unique reference.
   *
   * @return $this
   */
  public function setReference(string $reference): self {
    $this->reference = $reference;
    return $this;
  }

  /**
   * Returns the document URL.
   *
   * @return string
   *   The document url.
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * Sets the document URL.
   *
   * @param string $url
   *   The document url.
   *
   * @return $this
   */
  public function setUrl(string $url): self {
    $this->url = $url;
    return $this;
  }

  /**
   * Returns the document content.
   *
   * @return string
   *   The document content.
   */
  public function getContent(): string {
    return $this->content;
  }

  /**
   * Sets the document content.
   *
   * @param string $content
   *   The document content.
   *
   * @return $this
   */
  public function setContent(string $content): self {
    $this->content = $content;
    return $this;
  }

  /**
   * Returns the document metadata.
   *
   * @return array
   *   A nested array of field names and values.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Adds $value with field name $key to the document.
   *
   *  The format of $value is the same as specified in
   * \Drupal\search_api\Backend\BackendSpecificInterface::indexItems().
   *
   * @param string $key
   *   The key to use for the field.
   * @param array $values
   *   The values for the field.
   * @param string $type
   *   The field type.
   *
   * @return $this
   */
  public function setMetadata(string $key, array $values, string $type): self {
    foreach ($values as $value) {
      $value = $this->formatValue($value, $type);

      if ($value === NULL) {
        continue;
      }

      $this->metadata[$key][] = $value;
    }
    return $this;
  }

  /**
   * Returns the document language.
   *
   * @return string[]
   *   The document language.
   */
  public function getLanguages(): array {
    return $this->languages;
  }

  /**
   * Sets the document language.
   *
   * @param string $language
   *   A document language.
   *
   * @return $this
   */
  public function setLanguage(string $language): self {
    $this->languages[] = $language;
    return $this;
  }

  /**
   * Checks the document status.
   *
   * @param bool $status
   *   Desired status to compare.
   *
   * @return bool
   *   The status check result.
   */
  public function hasStatus(bool $status): bool {
    return $this->status === $status;
  }

  /**
   * Sets the document status.
   *
   * @param bool $status
   *   The document status.
   *
   * @return $this
   */
  public function setStatus(bool $status): self {
    $this->status = $status;
    return $this;
  }

  /**
   * Cast to primitive data type.
   *
   * @param mixed $value
   *   The value to be cast.
   * @param string $type
   *   The field type.
   *
   * @return mixed
   *   The value cast as primitive.
   */
  protected function formatValue($value, string $type) {
    switch ($type) {
      case 'date':
        $value = $this->formatDate($value);
        break;

      case 'boolean':
        $value = (bool) $value;
        break;

      case 'integer':
        $value = (int) $value;
        break;

      case 'decimal':
        $value = (float) $value;
        break;

      case 'text':
        $value = (string) $value;
      case 'string':
        if (!$value) {
          $value = NULL;
        }
        break;

      default:
        $value = NULL;
        break;
    }

    return $value;
  }

  /**
   * Tries to format a given date for ingestion.
   *
   * @param int|string $input
   *   The date to format (timestamp or string).
   *
   * @return null|string
   *   The formatted date as string or FALSE in case of invalid input.
   */
  protected function formatDate($input): ?string {
    try {
      $input = is_numeric($input) ? (int) $input : new \DateTime($input, timezone_open(DateTimeItemInterface::STORAGE_TIMEZONE));
    }
    catch (\Exception $e) {
      return NULL;
    }

    switch (TRUE) {
      case $input instanceof \DateTimeInterface:
        $input = clone $input;
        break;

      case \is_string($input):
      case is_numeric($input):
        // If date/time string: convert to timestamp first.
        if (\is_string($input)) {
          $input = strtotime($input);
        }
        try {
          $input = new \DateTime('@' . $input);
        }
        catch (\Exception $e) {
          $input = NULL;
        }
        break;

      default:
        $input = NULL;
        break;
    }

    if ($input) {
      // When we get here the input is always a datetime object.
      $input = $input->setTimezone(new \DateTimeZone('UTC'));
      return $input->format(\DateTimeInterface::RFC3339_EXTENDED);
    }

    return NULL;
  }

}
