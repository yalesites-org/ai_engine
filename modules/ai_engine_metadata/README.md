# AI Engine Metadata

## Overview

The AI Metadata module, developed by Yale ITS, is designed to add meta tags that are useful for ingestion into an AI feed and database. This module relies on the metatag module to store data.

## Features

- Adds metatag fields specifically to be used for AI metadata.
- Groups these fields into the metatag module.

## Metatag Fields

| Field               | Type    | Description                                        |
|---------------------|---------|----------------------------------------------------|
| ai_description      | String  | Text to be used for AI ingestion                   |
| ai_tags             | String  | If used w/ token/tags, will be comma separated list|
| ai_disable_indexing | Bool    | If set, entity should be excluded from the AI feed |

## Reading metatag values

To read these values, you can use the `Drupal\ai_engine_metadata\AiMetadataManager` class.

```php
use Drupal\ai_engine_metadata\AiMetadataManager;

$aiTags = $this->aiMetadataManager->getAiMetadata($entity);

// AI description.
$aiDescription = $aiTags['ai_description'];

// AI tags.
$aiDescription = $aiTags['ai_tags'];

// Checking if entity should be removed from AI feed.
$aiDisableIndexing = $aiTags['ai_disable_indexing'];
```

## Requirements

- Drupal 9 or later

## Contribution / Collaboration

You are welcome to contribute functionality, bug fixes, or documentation to this module. If you would like to suggest a fix or new functionality you may add a new issue to the GitHub issue queue or you may fork this repository and submit a pull request. For more help please see GitHub's article on fork, branch, and pull requests
