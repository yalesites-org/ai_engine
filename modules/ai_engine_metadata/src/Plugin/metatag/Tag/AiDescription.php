<?php

namespace Drupal\ai_engine_metadata\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * A tag to output a description for AI ingestion.
 *
 * @MetatagTag(
 *   id = "ai_description",
 *   label = @Translation("AI Description"),
 *   description = @Translation("Additional content to ingest into the AI model for this page."),
 *   name = "ai_description",
 *   group = "ai_engine",
 *   weight = 4,
 *   type = "label",
 *   long = TRUE,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AiDescription extends MetaNameBase {

}
