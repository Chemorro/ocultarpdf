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
        $this->version = '1.0.1';
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

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->installOverrides()) {
            return false;
        }

        if (!$this->registerHook('actionFrontControllerSetMedia')) {
            return false;
        }

        Configuration::updateValue('OCULTARPDF_BLOCKED_GROUPS', json_encode([]));

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        if (!Configuration::deleteByName('OCULTARPDF_BLOCKED_GROUPS')) {
            return false;
        }

        if (!$this->uninstallOverrides()) {
            return false;
        }

        if (!$this->unregisterHook('actionFrontControllerSetMedia')) {
            return false;
        }

        return true;
    }

    public function installOverrides()
    {
        $res = true;
        $res &= $this->addOverride('ProductController');
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();

        return (bool) $res;
    }

    public function uninstallOverrides()
    {
        $res = true;
        $res &= $this->removeOverride('ProductController');
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();

        return (bool) $res;
    }

    public function addOverride($className)
    {
        $filePath = _PS_MODULE_DIR_ . $this->name . '/override/controllers/front/' . $className . '.php';
        if (!file_exists($filePath)) {
            if (isset($this->context->controller) && isset($this->context->controller->errors)) {
                $this->context->controller->errors[] = sprintf(
                    $this->l('Archivo de override %s no encontrado para %s.'),
                    $filePath,
                    $className
                );
            }

            return false;
        }

        return PrestaShopAutoload::getInstance()->createOverride($className, $filePath);
    }

    public function removeOverride($className)
    {
        return PrestaShopAutoload::getInstance()->deleteOverride($className);
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit' . $this->name)) {
            $blockedGroups = Tools::getValue('OCULTARPDF_BLOCKED_GROUPS', []);
            if (!is_array($blockedGroups)) {
                $blockedGroups = [];
            }

            $blockedGroups = array_values(array_unique(array_map('intval', $blockedGroups)));
            Configuration::updateValue('OCULTARPDF_BLOCKED_GROUPS', json_encode($blockedGroups));
            $output .= $this->displayConfirmation($this->l('Configuración actualizada.'));
        }

        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $groups = Group::getGroups((int) $this->context->language->id);
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
            'request_uri' => Tools::safeOutput((string) $_SERVER['REQUEST_URI']),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller instanceof ProductController) {
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-front',
                'modules/' . $this->name . '/views/js/front.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }
}
