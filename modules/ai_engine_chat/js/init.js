(function() {
  /**
   * Initialization script for the AI engine chat feature.
   * This script creates and initializes the chat widget area on the webpage.
   * It relies on the drupalSettings object to configure the chat widget.
   *
   * @see ai_engine_chat_page_attachments_alter().
   */
  const chatWidget = document.createElement("div");
  chatWidget.setAttribute("id", "ai-engine-chat-widget");
  chatWidget.setAttribute(
    "data-azure-root",
    drupalSettings.ai_engine_chat.azure_base_url || ""
  );
  chatWidget.setAttribute(
    "data-initial-questions",
    drupalSettings.ai_engine_chat.initial_questions || ""
  );
  document.body.appendChild(chatWidget);
})();
