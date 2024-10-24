(function (Drupal) {
  Drupal.behaviors.linkDecoration = {
    attach: function (context, _settings) {
      const links = context.querySelectorAll("a[href='#launch-chat']");

      links.forEach((link) => {
        link.classList.add("ai-chatbot");
      });
    },
  };
})(Drupal);
