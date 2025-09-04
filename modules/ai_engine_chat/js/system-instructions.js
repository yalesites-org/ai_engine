/**
 * @file
 * JavaScript for the system instructions admin interface.
 */

(function ($, Drupal, once) {
  'use strict';

  /**
   * Character counter behavior for system instructions textarea.
   */
  Drupal.behaviors.systemInstructionsCharacterCount = {
    attach: function (context, settings) {
      const textarea = once('character-count', 'textarea[data-maxlength]', context);

      textarea.forEach(function (element) {
        const $textarea = $(element);
        const maxLength = parseInt($textarea.attr('data-maxlength'), 10);
        const warningClass = $textarea.attr('data-maxlength-warning-class') || 'warning';
        const errorClass = $textarea.attr('data-maxlength-limit-reached-class') || 'error';
        const warningThreshold = settings.aiEngineChatSystemInstructions?.warningThreshold || (maxLength * 0.9);
        
        // Create or find the counter element
        let $counter = $('#instructions-character-count');
        if ($counter.length === 0) {
          $counter = $('<div id="instructions-character-count" class="character-count"></div>');
          $textarea.after($counter);
        }

        function updateCounter() {
          const currentLength = $textarea.val().length;
          const remaining = maxLength - currentLength;
          
          // Update counter text
          if (currentLength > maxLength) {
            $counter.text(Drupal.t('@count characters (@over over recommended limit)', {
              '@count': currentLength,
              '@over': currentLength - maxLength
            }));
          } else {
            $counter.text(Drupal.t('@count / @max characters (@remaining remaining)', {
              '@count': currentLength,
              '@max': maxLength,
              '@remaining': remaining
            }));
          }

          // Update classes
          $counter.removeClass(warningClass + ' ' + errorClass);
          $textarea.removeClass(warningClass + ' ' + errorClass);

          if (currentLength > maxLength) {
            $counter.addClass(errorClass);
            $textarea.addClass(errorClass);
          } else if (currentLength > warningThreshold) {
            $counter.addClass(warningClass);
            $textarea.addClass(warningClass);
          }
        }

        // Initialize counter
        updateCounter();

        // Update counter on input
        $textarea.on('input keyup paste', updateCounter);
      });
    }
  };

})(jQuery, Drupal, once);