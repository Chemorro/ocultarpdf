<?php
/**
 * 2007-2024 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    ChemoP <contact@chemop.com>
 * @copyright 2007-2024 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class OcultarPdf extends Module
{
    public function __construct()
    {
        $this->name = 'ocultarpdf';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'ChemoP';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Ocultar PDF (Adjuntos)');
        $this->description = $this->l('Permite ocultar los enlaces de descarga de adjuntos de producto para grupos de clientes seleccionados.');

        $this->confirmUninstall = $this->l('¿Estás seguro de que quieres desinstalar? Todas las restricciones de visibilidad de adjuntos se perderán.');
    }

    /**
     * Instala el módulo, incluyendo el override del ProductController y la configuración inicial.
     *
     * @return bool
     */
    public function install()
    {
        // Verifica si la instalación base del módulo es exitosa.
        if (!parent::install()) {
            return false;
        }

        // Instala el override del ProductController. Es crucial que esto funcione.
        if (!$this->installOverrides()) {
            return false;
        }

        // Registra el hook para inyectar JavaScript en la página de producto.
        if (!$this->registerHook('actionFrontControllerSetMedia')) {
            return false;
        }

        // Establece un valor por defecto para la configuración (un array JSON vacío).
        Configuration::updateValue('OCULTARPDF_BLOCKED_GROUPS', json_encode([]));

        return true;
    }

    /**
     * Desinstala el módulo, incluyendo la eliminación del override y la configuración.
     *
     * @return bool
     */
    public function uninstall()
    {
        // Verifica si la desinstalación base del módulo es exitosa.
        if (!parent::uninstall()) {
            return false;
        }

        // Elimina la configuración del módulo.
        if (!Configuration::deleteByName('OCULTARPDF_BLOCKED_GROUPS')) {
            return false;
        }

        // Desinstala el override del ProductController.
        if (!$this->uninstallOverrides()) {
            return false;
        }

        // Desregistra el hook de JavaScript.
        if (!$this->unregisterHook('actionFrontControllerSetMedia')) {
            return false;
        }

        return true;
    }

    /**
     * Instala los overrides necesarios para el módulo.
     * @return bool True si los overrides se instalaron correctamente, false en caso contrario.
     */
    public function installOverrides()
    {
        $res = true;
        $res &= $this->addOverride('ProductController');
        // Siempre limpiar la caché después de instalar/desinstalar overrides
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        return $res;
    }

    /**
     * Desinstala los overrides del módulo.
     * @return bool True si los overrides se desinstalaron correctamente, false en caso contrario.
     */
    public function uninstallOverrides()
    {
        $res = true;
        $res &= $this->removeOverride('ProductController');
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();
        return $res;
    }

    /**
     * Helper para añadir un override de forma segura.
     * @param string $className El nombre de la clase a sobrescribir (ej. 'ProductController').
     * @return bool True si el override se creó correctamente, false en caso contrario.
     */
    public function addOverride($className)
    {
        $filePath = _PS_MODULE_DIR_ . $this->name . '/override/controllers/front/' . $className . '.php';
        if (!file_exists($filePath)) {
            $this->context->controller->errors[] = sprintf(
                $this->l('Archivo de override %s no encontrado para %s.'),
                $filePath,
                $className
            );
            return false;
        }
        return PrestaShopAutoload::getInstance()->createOverride($className, $filePath);
    }

    /**
     * Helper para eliminar un override de forma segura.
     * @param string $className El nombre de la clase cuyo override se va a eliminar.
     * @return bool True si el override se eliminó correctamente, false en caso contrario.
     */
    public function removeOverride($className)
    {
        return PrestaShopAutoload::getInstance()->deleteOverride($className);
    }

    /**
     * Muestra el contenido de la página de configuración del módulo en el Back Office.
     * @return string El HTML de la página de configuración.
     */
    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submit' . $this->name)) {
            $blockedGroups = Tools::getValue('OCULTARPDF_BLOCKED_GROUPS');
            if (!is_array($blockedGroups)) {
                $blockedGroups = [];
            }
            Configuration::updateValue('OCULTARPDF_BLOCKED_GROUPS', json_encode($blockedGroups));
            $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
        }
        return $output . $this->displayForm();
    }

    /**
     * Prepara y muestra el formulario de configuración del módulo en el Back Office.
     * @return string El HTML del formulario.
     */
    public function displayForm()
    {
        $groups = Group::getGroups($this->context->language->id);
        $currentSelectionJson = Configuration::get('OCULTARPDF_BLOCKED_GROUPS');
        $currentSelection = json_decode($currentSelectionJson, true);
        if (!is_array($currentSelection)) {
            $currentSelection = [];
        }

        $this->context->smarty->assign([
            'module_form' => true,
            'module_name' => $this->name,
            'current_group_selection' => $currentSelection,
            'groups' => $groups,
            'request_uri' => $_SERVER['REQUEST_URI'],
            'fields_value' => $this->getConfigFormValues(),
        ]);
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    protected function getConfigFormValues()
    {
        $blockedGroups = json_decode(Configuration::get('OCULTARPDF_BLOCKED_GROUPS'), true);
        if (!is_array($blockedGroups)) {
            $blockedGroups = [];
        }
        return ['OCULTARPDF_BLOCKED_GROUPS[]' => $blockedGroups];
    }

    /**
     * Hook para añadir archivos JavaScript al Front Office.
     * Se ejecuta en todas las páginas, por lo que debemos comprobar si estamos en la página del producto.
     * @param array $params
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        // Solo inyectar JS si estamos en la página del producto.
        if ($this->context->controller instanceof ProductController) {
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['position' => 'bottom', 'priority' => 150] // 'bottom' para asegurar que el DOM esté cargado
            );
        }
    }
}
