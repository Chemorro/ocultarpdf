/**
 * Capa de defensa en front: oculta bloques de adjuntos si el controlador
 * marcó el body con la clase `product-attachments-blocked`.
 */
document.addEventListener('DOMContentLoaded', function () {
  if (!document.body.classList.contains('product-attachments-blocked')) {
    return;
  }

  var selectors = [
    '.product-attachments',
    '#product-attachments',
    '.attachments',
    '.attachment_top'
  ];

  selectors.forEach(function (selector) {
    var nodes = document.querySelectorAll(selector);
    nodes.forEach(function (node) {
      node.style.display = 'none';
    });
  });
});
