window.onload = function () {
  jQuery('#carousel-track').slick({
    autoplay: false,
    autoplaySpeed: 1500,
    arrows: true,
    prevArrow: '.carrusel-prev',
    nextArrow: '.carrusel-next',
    slidesToShow: 4,
    slidesToScroll: 1
  });
};

