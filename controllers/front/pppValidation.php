<?php
/**
 * 2007-2019 PrestaShop
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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2019 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

include_once _PS_MODULE_DIR_.'paypal/classes/AbstractMethodPaypal.php';
include_once _PS_MODULE_DIR_.'paypal/controllers/front/abstract.php';

/**
 * Validate PPP payment
 */
class PaypalPppValidationModuleFrontController extends PaypalAbstarctModuleFrontController
{
    public function init()
    {
        parent::init();
        $this->values['short_cut'] = Tools::getvalue('short_cut');
        $this->values['paymentId'] = Tools::getvalue('paymentId');
        $this->values['payerId'] = Tools::getvalue('PayerID');
    }

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $method_ppp = AbstractMethodPaypal::load('PPP');
        $paypal = Module::getInstanceByName($this->name);
        try {
            $method_ppp->setParameters($this->values);
            $method_ppp->validation();
            $cart = Context::getContext()->cart;
            $customer = new Customer($cart->id_customer);
            $this->redirectUrl = 'index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$paypal->id.'&id_order='.$paypal->currentOrder.'&key='.$customer->secure_key;
        } catch (PayPal\Exception\PayPalConnectionException $e) {
            $decoded_message = Tools::jsonDecode($e->getData());
            $this->errors['error_code'] = $e->getCode();
            $this->errors['error_msg'] = $decoded_message->message;
            $this->errors['msg_long'] = $decoded_message->name.' - '.$decoded_message->details[0]->issue;
        } catch (PayPal\Exception\PayPalInvalidCredentialException $e) {
            $this->errors['error_msg'] = $e->errorMessage();
        } catch (PayPal\Exception\PayPalMissingCredentialException $e) {
            $this->errors['error_msg'] = $paypal->l('Invalid configuration. Please check your configuration file');
        } catch (Exception $e) {
            $this->errors['error_code'] = $e->getCode();
            $this->errors['error_msg'] = $e->getMessage();
        }

        Context::getContext()->cookie->__unset('paypal_plus_payment');
        Context::getContext()->cookie->__unset('paypal_pSc');
        Context::getContext()->cookie->__unset('paypal_pSc_payerid');
        Context::getContext()->cookie->__unset('paypal_pSc_email');

        if (!empty($this->errors)) {
            $this->redirectUrl = Context::getContext()->link->getModuleLink($this->name, 'error', $this->errors);
        }
    }
}
