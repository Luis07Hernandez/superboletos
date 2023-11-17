(function ($, Drupal) {
  Drupal.behaviors.customCarouselBehavior = {
    attach: function (context, settings) {
      document.addEventListener("DOMContentLoaded", function () {
        const carousel = document.getElementById("carousel");
        const scrollLeftButton = document.getElementById("scrollLeft");
        const scrollRightButton = document.getElementById("scrollRight");

        // Scroll the carousel to the left
        scrollLeftButton.addEventListener("click", () => {
          carousel.scrollBy({
            left: -carousel.offsetWidth,
            behavior: "smooth",
          });
        });

        // Scroll the carousel to the right
        scrollRightButton.addEventListener("click", () => {
          carousel.scrollBy({ left: carousel.offsetWidth, behavior: "smooth" });
        });
      });
    },
  };
})(jQuery, Drupal);
