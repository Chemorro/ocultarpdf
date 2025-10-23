<?php
class OcultarpdfDownloadModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();

        $id_product = (int)Tools::getValue('id_product');
        if (!$id_product) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        // Comprueba permisos globales del módulo
        if (!$this->module->isCustomerAllowed()) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        // Obtén los attachments del producto (ejemplo con Product::getAttachments)
        $attachments = Product::getAttachments($this->context->language->id, $id_product);
        if (empty($attachments)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        // Si quieres, puedes aceptar ?id_attachment= para elegir cuál servir. Aquí tomamos el primero.
        $att = $attachments[0];

        // NOTA: En instalaciones estándar de PrestaShop, los attachments se sirven por la URL /download/attachment/{id}/{filename}.
        // Redirigir a esa URL NO evita accesos directos si el archivo es público. Para protección completa se recomienda:
        // - Servir aquí mismo el fichero (leer del disco y emitir headers) o
        // - Mover los archivos fuera del directorio público y servirlos únicamente desde este controlador.
        // Aquí, por compatibilidad mínima, redirigimos a la URL nativa (adapta según tu estrategia de seguridad).
        $url = $this->context->link->getPageLink('attachment', true, null, 'id_attachment=' . (int)$att['id_attachment']);
        Tools::redirect($url);
    }
}