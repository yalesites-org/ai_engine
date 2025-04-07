<?php

namespace Drupal\ai_engine_metadata\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Disables indexing for this page in an AI feed.
 *
 * @MetatagTag(
 *   id = "ai_disable_indexing",
 *   label = @Translation("Disable indexing for AI feeds."),
 *   description = @Translation("Remove this content from the AI index."),
 *   name = "ai_disable_indexing",
 *   group = "ai_engine",
 *   weight = 2,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AiDisableIndexing extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []): array {
    $form = [
      '#type' => 'checkbox',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#default_value' => ($this->value === 'disabled') ? 1 : 0,
      '#required' => $element['#required'] ?? FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestFormXpath(): array {
    return ["//input[@name='{$this->id}' and @type='checkbox']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputExistsXpath(): array {
    return ["//" . $this->htmlTag . "[@" . $this->htmlNameAttribute . "='{$this->name}' and @content='disabled']"];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestOutputValuesXpath(array $values): array {
    return ["//" . $this->htmlTag . "[@" . $this->htmlNameAttribute . "='{$this->name}' and @content='disabled']"];
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value): void {
    if ($value == "1") {
      $value = 'disabled';
    }
    elseif ($value == "0") {
      $value = 'enabled';
    }

    parent::setValue($value);
  }

}
