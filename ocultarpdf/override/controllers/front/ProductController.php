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
    protected function assignAttachments()
    {
        parent::assignAttachments();

        if (!Configuration::get('PS_ATTACHMENT_ENABLE') || !Validate::isLoadedObject($this->product)) {
            return;
        }

        $blockedGroupIds = json_decode((string) Configuration::get('OCULTARPDF_BLOCKED_GROUPS'), true);
        if (!is_array($blockedGroupIds) || empty($blockedGroupIds)) {
            return;
        }

        $blockedGroupIds = array_map('intval', $blockedGroupIds);

        if ($this->context->customer->isLogged()) {
            $customerGroups = array_map('intval', $this->context->customer->getGroups());
        } else {
            $customerGroups = [
                (int) Configuration::get('PS_UNIDENTIFIED_GROUP'),
                (int) Configuration::get('PS_GUEST_GROUP'),
            ];
        }

        $customerIsBlocked = (bool) array_intersect($customerGroups, $blockedGroupIds);
        if (!$customerIsBlocked) {
            return;
        }

        $productVars = $this->context->smarty->getTemplateVars('product');
        if (is_array($productVars) && isset($productVars['attachments']) && is_array($productVars['attachments'])) {
            $productVars['attachments'] = [];
            $this->context->smarty->assign('product', $productVars);
        }

        $bodyClasses = $this->context->smarty->getTemplateVars('body_classes');
        if (!is_array($bodyClasses)) {
            $bodyClasses = [];
        }
        $bodyClasses['product-attachments-blocked'] = true;
        $this->context->smarty->assign('body_classes', $bodyClasses);
    }
}
