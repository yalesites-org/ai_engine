<?php

namespace Drupal\ai_engine_system_instructions\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Node\Block\ListItem;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Inline\Text;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;

/**
 * Service for detecting and formatting text content using CommonMark parser.
 */
class TextFormatDetectionService {

  /**
   * Format types.
   */
  const FORMAT_MARKDOWN = 'markdown';
  const FORMAT_PLAIN_TEXT = 'plain_text';

  /**
   * The CommonMark parser.
   *
   * @var \League\CommonMark\Parser\MarkdownParser
   */
  protected $parser;

  /**
   * Constructor.
   */
  public function __construct() {
    // Create CommonMark environment with core extensions
    $environment = new Environment();
    $environment->addExtension(new CommonMarkCoreExtension());
    
    $this->parser = new MarkdownParser($environment);
  }

  /**
   * Detect the format of the given text using regex patterns.
   *
   * @param string $text
   *   The text to analyze.
   *
   * @return array
   *   Array with 'format' (string) and 'confidence' (float 0-1).
   */
  public function detectFormat(string $text): array {
    $text = trim($text);
    
    if (empty($text)) {
      return [
        'format' => self::FORMAT_PLAIN_TEXT,
        'confidence' => 1.0,
      ];
    }

    // Simple regex-based detection for markdown patterns
    $markdown_score = $this->calculateMarkdownScore($text);
    
    // If markdown score is above threshold, consider it markdown
    if ($markdown_score > 0.3) {
      return [
        'format' => self::FORMAT_MARKDOWN,
        'confidence' => $markdown_score,
      ];
    }

    return [
      'format' => self::FORMAT_PLAIN_TEXT,
      'confidence' => 1.0 - $markdown_score,
    ];
  }

  /**
   * Calculate a score indicating how likely the text is markdown (0-1).
   *
   * @param string $text
   *   The text to analyze.
   *
   * @return float
   *   Score from 0 (definitely not markdown) to 1 (definitely markdown).
   */
  protected function calculateMarkdownScore(string $text): float {
    $score = 0.0;

    // Check for markdown headers (including compressed ones)
    $header_matches = preg_match_all('/#{1,6}\s+/', $text);
    if ($header_matches) {
      $score += min($header_matches * 0.3, 0.6);
    }

    // Check for markdown lists (including compressed ones)
    $list_matches = preg_match_all('/\s*[-*+]\s+/', $text);
    if ($list_matches) {
      $score += min($list_matches * 0.1, 0.4);
    }

    // Check for numbered lists
    $numbered_list_matches = preg_match_all('/\s*\d+\.\s+/', $text);
    if ($numbered_list_matches) {
      $score += min($numbered_list_matches * 0.1, 0.3);
    }

    // Check for bold text
    $bold_matches = preg_match_all('/\*\*[^*]+\*\*/', $text);
    if ($bold_matches) {
      $score += min($bold_matches * 0.05, 0.2);
    }

    // Check for italic text
    $italic_matches = preg_match_all('/(?<!\*)\*[^*]+\*(?!\*)/', $text);
    if ($italic_matches) {
      $score += min($italic_matches * 0.05, 0.2);
    }

    return min($score, 1.0);
  }

  /**
   * Format text based on its detected format.
   *
   * @param string $text
   *   The text to format.
   * @param string|null $format
   *   Optional format override. If null, format will be detected.
   *
   * @return string
   *   The formatted text.
   */
  public function formatText(string $text, ?string $format = NULL): string {
    $text = trim($text);
    
    if (empty($text)) {
      return '';
    }

    // Detect format if not provided
    if ($format === NULL) {
      $detection = $this->detectFormat($text);
      $format = $detection['format'];
    }

    if ($format === self::FORMAT_MARKDOWN) {
      return $this->formatMarkdownText($text);
    }

    return $this->formatPlainText($text);
  }


  /**
   * Format text assuming it's markdown by adding proper spacing.
   *
   * @param string $text
   *   The markdown text to format.
   *
   * @return string
   *   The formatted markdown text with proper spacing but preserved syntax.
   */
  protected function formatMarkdownText(string $text): string {
    $text = trim($text);

    // Step 1: Fix headers that are compressed with previous text
    // Pattern: "word## Header" or "word.## Header"
    $text = preg_replace('/([a-zA-Z.,!?])(\#{1,6}\s+)/', "$1\n\n$2", $text);
    $text = preg_replace('/([a-zA-Z.,!?])\s+(\#{1,6}\s+)/', "$1\n\n$2", $text);

    // Step 2: Fix headers that have content flowing after them
    // Break when we have a capitalized word followed by lowercase words (indicates new sentence)
    $text = preg_replace('/(\#{1,6}\s+[^#\n]+?)\s+([A-Z][a-z]+\s+[a-z])/', "$1\n\n$2", $text);

    // Step 3: Fix list items that come directly after text or colons
    // Pattern: "word: - Item" or "word. - Item" or "word - Item"
    $text = preg_replace('/([a-zA-Z.,!?:])(-\s+)/', "$1\n$2", $text);
    $text = preg_replace('/([a-zA-Z.,!?:])\s(-\s+)/', "$1\n$2", $text);
    $text = preg_replace('/([a-zA-Z.,!?:])\s+(-\s+)/', "$1\n$2", $text);

    // Step 4: Skip general sentence breaking for now - it's too complex and breaks headers
    // Instead, rely on headers and lists being formatted correctly by other steps

    // Step 5: Handle numbered lists (after sentence breaks to avoid conflicts)
    // Handle both "1. Item" and "1.Item" patterns
    $text = preg_replace('/([a-zA-Z.,!?:])\s*(\d+\.)(\s*)([A-Z])/', "$1\n$2 $4", $text);

    // Step 6: Clean up excessive whitespace but preserve intentional formatting
    $text = preg_replace('/[ \t]+/', ' ', $text);

    // Step 7: Clean up excessive line breaks (more than 2)
    $text = preg_replace('/\n{3,}/', "\n\n", $text);

    return trim($text);
  }


  /**
   * Format text assuming it's plain text.
   *
   * @param string $text
   *   The plain text to format.
   *
   * @return string
   *   The formatted plain text.
   */
  protected function formatPlainText(string $text): string {
    // Remove excessive whitespace
    $text = trim($text);
    
    // Replace multiple spaces with single spaces
    $text = preg_replace('/[ \t]+/', ' ', $text);
    
    // Add line breaks after sentences followed by capital letters
    $text = preg_replace('/([.!?])\s+([A-Z])/', "$1\n\n$2", $text);
    
    // Break up very long lines (over 100 characters) at natural break points
    $lines = explode("\n", $text);
    $formatted_lines = [];
    
    foreach ($lines as $line) {
      if (strlen($line) > 100) {
        // Try to break at sentence boundaries first
        $sentences = preg_split('/([.!?])\s+/', $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        $current_line = '';
        
        for ($i = 0; $i < count($sentences); $i += 2) {
          $sentence = $sentences[$i];
          $delimiter = $sentences[$i + 1] ?? '';
          
          if (strlen($current_line . $sentence . $delimiter) > 100 && !empty($current_line)) {
            $formatted_lines[] = trim($current_line);
            $current_line = $sentence . $delimiter;
          }
          else {
            $current_line .= $sentence . $delimiter;
          }
        }
        
        if (!empty($current_line)) {
          $formatted_lines[] = trim($current_line);
        }
      }
      else {
        $formatted_lines[] = $line;
      }
    }
    
    $text = implode("\n", $formatted_lines);
    
    // Clean up excessive line breaks
    $text = preg_replace('/\n{3,}/', "\n\n", $text);
    
    return trim($text);
  }

}