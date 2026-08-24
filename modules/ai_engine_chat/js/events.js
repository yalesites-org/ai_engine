/**
 * Adds event listeners to launch the chat app.
 *
 * The chat widget is an embedded React app that is initially hidden off screen.
 * Any link with href="#launch-chat" will trigger a click event within this app
 * and open the modal chat window.
 */
document.addEventListener("DOMContentLoaded", function () {
  const debug = false;

  var launchLinks = document.querySelectorAll('a[href="#launch-chat"]');
  var tries = 0;

  launchLinks.forEach(function (link) {
    link.classList.add("ai-chatbot");
    link.addEventListener("click", function (event) {
      event.preventDefault();
      triggerChatLaunch();
    });
  });

  if (window.location.hash === "#launch-chat") {
    setTimeout(triggerChatLaunch, 1);
  }

  function triggerChatLaunch() {
    // Trigger a click on the button with id "launch-chat-modal".
    var launchButton = document.getElementById("launch-chat-modal");
    if (launchButton) {
      launchButton.click();
    }
    else if (tries < 3) {
      tries += 1;
      if (debug) {
        console.log("Retrying to find the launch button...");
        console.log("Try number: " + tries);
      }
      setTimeout(triggerChatLaunch, 1000);
    }
    else {
      console.error("Launch button not found after 3 attempts.");
    }
  }
});
