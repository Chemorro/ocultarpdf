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

class ProductController extends ProductControllerCore
{
    /**
     * Sobrescribe el método assignAttachments para filtrar los adjuntos
     * basándose en los grupos de clientes configurados en el módulo OcultarPDF.
     *
     * @return void
     */
    protected function assignAttachments()
    {
        // Llama al método padre para que PrestaShop cargue los adjuntos como lo hace normalmente.
        parent::assignAttachments();

        if (Configuration::get('PS_ATTACHMENT_ENABLE') && Validate::isLoadedObject($this->product)) {
            $productVars = $this->context->smarty->getTemplateVars('product');

            $blockedGroupIdsJson = Configuration::get('OCULTARPDF_BLOCKED_GROUPS');
            $blockedGroupIds = json_decode($blockedGroupIdsJson, true);

            // Si no hay grupos bloqueados configurados o si la decodificación falló, no hacemos nada.
            if (empty($blockedGroupIds) || !is_array($blockedGroupIds)) {
                return;
            }

            // Obtener los IDs de los grupos a los que pertenece el cliente actual.
            $customerGroups = [];
            if ($this->context->customer->isLogged()) {
                $customerGroups = $this->context->customer->getGroups();
            } else {
                // Cliente no logueado, consideramos los grupos por defecto de "Visitante" y "Invitado".
                $customerGroups = [
                    (int) Configuration::get('PS_UNIDENTIFIED_GROUP'),
                    (int) Configuration::get('PS_GUEST_GROUP')
                ];
            }

            // Comprueba si el cliente actual pertenece a CUALQUIERA de los grupos bloqueados.
            $customerIsBlocked = false;
            foreach ($customerGroups as $customerGroupId) {
                if (in_array($customerGroupId, $blockedGroupIds)) {
                    $customerIsBlocked = true;
                    break;
                }
            }

            if ($customerIsBlocked) {
                // 1. Vaciar los adjuntos en la variable $product para Smarty.
                // Esto debería hacer que la plantilla no los renderice desde PHP.
                if (isset($productVars['attachments']) && is_array($productVars['attachments'])) {
                    $productVars['attachments'] = [];
                    $this->context->smarty->assign('product', $productVars);
                }

                // 2. Además, añadir una clase al body del HTML.
                // Esto es una señalización para el JavaScript posterior o para CSS directo.
                $body_classes = $this->context->smarty->getTemplateVars('body_classes');
                if (!is_array($body_classes)) {
                    $body_classes = [];
                }
                // La clave es el nombre de la clase, que utilizaremos en el JS.
                $body_classes['product-attachments-blocked'] = true;
                $this->context->smarty->assign('body_classes', $body_classes);
            }
        }
    }
}
