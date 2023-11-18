document.addEventListener("DOMContentLoaded", function () {
  const container = document.querySelector("#carousel-track");
  const children = document.querySelector("#carrusel-item");

  document
    .querySelector(".carrusel-prev")
    .addEventListener("click", function () {
      // Calcula el ancho del elemento a desplazar
      var scrollAmount = children.offsetWidth;
      // Desplaza el contenedor suavemente hacia la izquierda
      container.scrollBy({ left: -scrollAmount, behavior: "smooth" });
    });

  document
    .querySelector(".carrusel-next")
    .addEventListener("click", function () {
      var scrollAmount = children.offsetWidth;
      // Desplaza el contenedor suavemente hacia la derecha
      container.scrollBy({ left: scrollAmount, behavior: "smooth" });
    });
});
