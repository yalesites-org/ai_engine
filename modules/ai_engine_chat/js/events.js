/**
 * Adds event listeners to launch the chat app.
 *
 * The chat widget is an embedded React app that is initially hidden off screen.
 * Any link with href="#launch-chat" will trigger a click event within this app
 * and open the modal chat window.
 */
document.addEventListener("DOMContentLoaded", function () {
  var launchLinks = document.querySelectorAll('a[href="#launch-chat"]');
  launchLinks.forEach(function (link) {
    link.classList.add("ai-chatbot");
    link.addEventListener("click", function (event) {
      event.preventDefault();
      // Trigger a click on the button with id "launch-chat-modal".
      var launchButton = document.getElementById("launch-chat-modal");
      if (launchButton) {
        launchButton.click();
      }
    });
  });
});
