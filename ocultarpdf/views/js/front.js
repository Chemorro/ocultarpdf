/**
 * Este script se ejecuta en el Front Office.
 * Oculta los elementos de adjuntos de producto si el <body> tiene la clase
 * 'product-attachments-blocked', que es inyectada por el override del ProductController.
 * Actúa como una capa de seguridad si el tema renderiza los adjuntos mediante JS o si
 * el vaciado de la variable Smarty no es suficiente.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Comprueba si el elemento <body> tiene la clase de bloqueo inyectada por PHP.
    if (document.body.classList.contains('product-attachments-blocked')) {
        // Seleccionamos el contenedor principal de los adjuntos basándonos en tu product.tpl.
        // Este selector '.attachment_top' se encuentra dentro de <div class="block_center">
        // y es el div que contiene los elementos '<span>' con los adjuntos.
        const attachmentContainer = document.querySelector('.attachment_top');

        if (attachmentContainer) {
            // Si lo encontramos, lo ocultamos por completo.
            attachmentContainer.style.display = 'none';
        }

        // Si tu tema tiene un div padre más grande con el block name="product_attachments",
        // o cualquier otro selector para el contenedor de adjuntos, puedes añadirlo aquí:
        // const attachmentsBlock = document.querySelector('.product_attachments'); // Otra clase posible
        // if (attachmentsBlock) {
        //     attachmentsBlock.style.display = 'none';
        // }
    }
});