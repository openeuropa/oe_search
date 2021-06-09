<?php

declare(strict_types = 1);

namespace Drupal\oe_search;

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
        // This will always be a timestamp, we just transform to millis.
        // @see \Drupal\search_api\Plugin\search_api\data_type\DateDataType::getValue()
        $value = $value * 1000;
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

}
