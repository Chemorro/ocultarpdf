<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class OcultarPdf extends Module
{
    public function __construct()
    {
        $this->name = 'ocultarpdf';
        $this->tab = 'front_office_features';
        $this->version = '1.1.0';
        $this->author = 'ChemoP';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ocultar PDF (Adjuntos)');
        $this->description = $this->l('Permite ocultar los enlaces de descarga de adjuntos de producto para grupos de clientes seleccionados (global).');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar? Se eliminarán las restricciones guardadas.');
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        // Configuración por defecto: array vacío = nadie ve los PDFs (oculto por defecto)
        Configuration::updateValue('OCULTARPDF_ALLOWED_GROUPS', json_encode([]));

        // Hooks (sin overrides)
        if (
            !$this->registerHook('displayProductButtons') ||
            !$this->registerHook('actionFrontControllerSetMedia') ||
            !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        Configuration::deleteByName('OCULTARPDF_ALLOWED_GROUPS');

        $this->unregisterHook('displayProductButtons');
        $this->unregisterHook('actionFrontControllerSetMedia');
        $this->unregisterHook('displayBackOfficeHeader');

        return true;
    }

    /**
     * Página de configuración (Back Office)
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            // CSRF: comprobamos token mínimo
            $token = Tools::getValue('token');
            if (!empty($token) && $token === Tools::getAdminTokenLite('AdminModules')) {
                $allowedGroups = Tools::getValue('OCULTARPDF_ALLOWED_GROUPS', []);
                if (!is_array($allowedGroups)) {
                    $allowedGroups = [];
                }
                Configuration::updateValue('OCULTARPDF_ALLOWED_GROUPS', json_encode(array_map('intval', $allowedGroups)));
                $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
            } else {
                $output .= $this->displayError($this->l('Token de seguridad inválido.'));
            }
        }

        $this->context->smarty->assign([
            'module_name' => $this->name,
            'groups' => Group::getGroups($this->context->language->id),
            'current_selection' => json_decode(Configuration::get('OCULTARPDF_ALLOWED_GROUPS'), true) ?: [],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'admin_token' => Tools::getAdminTokenLite('AdminModules'),
        ]);

        $output .= $this->display(__FILE__, 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Comprueba si el cliente actual pertenece a alguno de los grupos permitidos.
     * Comportamiento: si la lista de allowed groups está vacía => NINGÚN grupo tiene permiso (oculto por defecto).
     */
    public function isCustomerAllowed()
    {
        $allowed = json_decode(Configuration::get('OCULTARPDF_ALLOWED_GROUPS'), true);
        if (!is_array($allowed)) {
            $allowed = [];
        }
        // Si vacío => nadie permitido
        if (empty($allowed)) {
            return false;
        }

        $customerGroups = [];
        if ($this->context->customer && $this->context->customer->isLogged()) {
            $customerGroups = $this->context->customer->getGroups();
        } else {
            // visitante anónimo: usar PS_UNIDENTIFIED_GROUP
            $customerGroups = [(int)Configuration::get('PS_UNIDENTIFIED_GROUP')];
        }

        return (bool) array_intersect($allowed, $customerGroups);
    }

    /**
     * Hook que inyecta el enlace/plantilla del módulo en la ficha de producto (displayProductButtons).
     * Si el cliente NO está permitido, devolvemos cadena vacía (no mostramos enlace del módulo).
     * Además, inyectamos una variable JS/class que puede usarse para ocultar el bloque de attachments nativo.
     */
    public function hookDisplayProductButtons($params)
    {
        // Si el cliente no está permitido, devolvemos '' para no mostrar nuestro enlace
        if (!$this->isCustomerAllowed()) {
            // También asignamos una variable JS para ocultar adjuntos nativos del tema
            $this->context->smarty->assign('ocultarpdf_block_attachments', true);
            return '';
        }

        // Cliente permitido: podemos mostrar un enlace seguro a la descarga vía módulo (controllers/front/download)
        $id_product = 0;
        if (isset($params['product']) && $params['product'] instanceof Product) {
            $id_product = (int)$params['product']->id;
        } elseif (Tools::getValue('id_product')) {
            $id_product = (int)Tools::getValue('id_product');
        }

        // Validación extra
        if ($id_product <= 0) {
            return '';
        }

        // Genera link al controlador del módulo que servirá / redirigirá la descarga tras comprobar permisos
        $pdf_url = $this->context->link->getModuleLink($this->name, 'download', ['id_product' => $id_product], true);

        $this->context->smarty->assign([
            'pdf_url' => $pdf_url,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/displayPdf.tpl');
    }

    /**
     * Hook para añadir JS en front (usamos JS para ocultar DOM del tema si corresponde).
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller instanceof ProductController) {
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['priority' => 150, 'position' => 'bottom']
            );

            // Pasamos variable a JS: si el cliente NO está permitido, el JS ocultará elementos nativos
            Media::addJsDef([
                'ocultarpdf_block_attachments' => !$this->isCustomerAllowed()
            ]);
        }
    }
}