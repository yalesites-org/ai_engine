<?php

namespace Drupal\ai_engine_metadata\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * A tag to output a collection of tags for AI ingestion.
 *
 * @MetatagTag(
 *   id = "ai_tags",
 *   label = @Translation("AI Tags"),
 *   description = @Translation("Additional tags to ingest into the AI model for this page."),
 *   name = "ai_tags",
 *   group = "ai_engine",
 *   weight = 4,
 *   type = "label",
 *   long = FALSE,
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class AiTags extends MetaNameBase {

}
