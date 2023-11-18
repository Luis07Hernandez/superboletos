function buildSelect(selector, customPlaceHolder = null, isAjaxReload = false) {
  const $ = jQuery;

  $(selector).each(function () {
    var $this = $(this), numberOfOptions = $(this).children('option').length;

    $this.addClass('select-hidden');
    $this.wrap('<div class="select"></div>');

    var $buttonContainer = $('<div class="button-container"></div>');

    $('.view-filters').append($buttonContainer);

    for (var i = 0; i < numberOfOptions; i++) {
      var optionText = $this.children('option').eq(i).text();
      var optionValue = $this.children('option').eq(i).val();
      var isSelected = $this.children('option').eq(i).is(':selected');

      var $button = $('<button/>', {
        text: optionText,
        'class': isSelected ? 'button-selected' : '',
        val: optionValue,
        type: 'button',
        click: function (e) {
          e.stopPropagation();
          $this.val($(this).val());
          $this.trigger('change');
          $(this).siblings().removeClass('button-selected');
          $(this).addClass('button-selected');
          $(`${formID} .form-submit`).click();
        }
      }).addClass('text-white bg-gray-800 hover:bg-gray-900 focus:outline-none focus:ring-4 focus:ring-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-gray-800 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700');

      $buttonContainer.append($button);
    }

    // Manejar el evento de clic fuera de los botones para cerrar cualquier menú abierto.
    $(document).click(function () {
      $buttonContainer.find('.button-selected').removeClass('active');
    });

    $(formID).hide();

  });

}

const blockID = '.view-last-events';
const formID = '#views-exposed-form-last-events-block-1';

if (jQuery(blockID).length) {
    (function ($) {
        $(document).ready(function () {
            // Función callback para el observador
            const callback = function (mutationsList, observer) {
                for (let mutation of mutationsList) {
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        for (let node of addedNodes) {
                            if (node.nodeType === Node.ELEMENT_NODE && node.matches('.view-last-events')) {

                                buildSelect(`${formID} .form-select`);
                            }
                        }
                    }
                }
            };

            // Configuración del observador (en este caso, solo estamos observando la adición de nodos hijos)
            const config = { childList: true, subtree: true };

            // Crear una instancia del observador con la función callback
            const observer = new MutationObserver(callback);

            // Comienza a observar el documento con la configuración configurada
            observer.observe(document, config);
        })

        buildSelect(`${formID} .form-select`, 'Escuelas', false);
    })(jQuery);
}
