<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use OpenEuropa\EuropaSearchClient\Model\DocumentBase;

/**
 * Class for holding and preparing data needed for ingestion of one item.
 */
class IngestionDocument extends DocumentBase {

  /**
   * Whether this document is eligible for ingestion.
   *
   * @var bool
   */
  protected $canBeIngested = FALSE;

  /**
   * Adds $value with field name $key to the document.
   *
   * The format of $value is the same as specified in
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
   *
   * @see \Drupal\search_api\Backend\BackendSpecificInterface::indexItems()
   */
  public function addMetadata(string $key, array $values, string $type): self {
    // @todo What happens if $this->metadata[$key] already exists?
    foreach ($values as $value) {
      $value = $this->formatValue($value, $type);
      if ($value !== NULL) {
        $this->metadata[$key][] = $value;
      }
    }
    return $this;
  }

  /**
   * Checks whether this ingestion document is allowed for ingestion.
   *
   * @return bool
   *   Whether this ingestion document is allowed for ingestion.
   */
  public function canBeIngested(): bool {
    return $this->canBeIngested;
  }

  /**
   * Sets whether this document can be ingested.
   *
   * @param bool $can_be_ingested
   *   Whether this document can be ingested.
   *
   * @return $this
   */
  public function setCanBeIngested(bool $can_be_ingested): self {
    $this->canBeIngested = $can_be_ingested;
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
