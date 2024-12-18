<?php

/**
 * namespace
 */
namespace common\modules\orderPayment;

/**
 * used classes
 */
use common\classes\modules\ModulePayment;
use common\classes\modules\ModuleStatus;
use common\classes\modules\ModuleSortOrder;

/**
 * class declaration
 */
class paymentoscommerce extends ModulePayment {

    /**
     * variables
     */
    var $code, $title, $description, $enabled;

    /**
     * default values for translation
     */
    protected $defaultTranslationArray = [
        'MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEXT_TITLE' => 'PaymentOscommerce Payment Gateway',
        'MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEXT_DESCRIPTION' => 'Pay using your credit balance',
        'MODULE_PAYMENT_PAYMENTOSCOMMERCE_ERROR' => 'There has been an error processing your payment',
    ];

    /**
     * class constructor
     */
    function __construct() {
        parent::__construct();

        $this->code = 'paymentoscommerce';
        $this->title = MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEXT_TITLE;
        $this->description = MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEXT_DESCRIPTION;

        if (!defined('MODULE_PAYMENT_PAYMENTOSCOMMERCE_STATUS')) {
            $this->enabled = false;
            return false;
        }

        $this->update_status();
    }

    function update_status() {
        if (defined('MODULE_PAYMENT_PAYMENTOSCOMMERCE_STATUS')) {
            $this->enabled = true;
            return true;
        }
    }

    function selection() {
        if (!$this->enabled) {
            return [];
        }

        return [
            'id' => $this->code,
            'module' => $this->title,
        ];
    }

    function generateRandomString($length = 6): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    function process_button() {
            $currency = \Yii::$app->settings->get('currency');
            /** @var OrderAbstract $order */
            $order = $this->manager->getOrderInstance();
            $amount= $order->info['total_inc_tax'];

            $merchant_id = MODULE_PAYMENT_PAYMENTOSCOMMERCE_MERCHANT_ID;
            $partner_name = MODULE_PAYMENT_PAYMENTOSCOMMERCE_PARTNER_NAME;
            $secret_key = MODULE_PAYMENT_PAYMENTOSCOMMERCE_SECRET_KEY;
            $redirect_url = MODULE_PAYMENT_PAYMENTOSCOMMERCE_REDIRECT_URL;
            $test_url = MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEST_URL;

            $merchantTransactionId = $this->generateRandomString();

            $checksum_maker = $merchant_id . '|' . $partner_name . '|' . $amount . '|' . $merchantTransactionId . '|' . $redirect_url. '|' . $secret_key;
                
            $checksum = md5($checksum_maker);
 
            $html = "<script>
                        window.onload = function() {
                            var form = document.getElementById('frmCheckoutConfirm');
                            form.action = '$test_url';
                        }
                    </script>";
            
            $html .= '<input type="hidden" name="toid" value="' . htmlspecialchars($merchant_id) . '">';
            $html .= '<input type="hidden" name="totype" value="' . htmlspecialchars($partner_name) . '">';
            $html .= '<input type="hidden" name="merchantRedirectUrl" value="' . htmlspecialchars($redirect_url) . '">';
            $html .= '<input type="hidden" name="amount" value="' . htmlspecialchars($amount) . '">';
            $html .= '<input type="hidden" name="currency" value="' . htmlspecialchars($currency) . '">';
            $html .= '<input type="hidden" name="description" value="' . htmlspecialchars($merchantTransactionId) . '">';
            $html .= '<input type="hidden" name="checksum" value="' . htmlspecialchars($checksum) . '">';
        
            return $html;
    }
        
    

    function before_process() {
        // Logic can be added here if necessary before processing the payment
    }

    function after_process() {
        $order = $this->manager->getOrderInstance();

        if ($this->paymentSuccess) {
            $order->status = 'Paid';
            $this->sendConfirmationEmail($order);
            error_log("Order status updated to Paid for Order ID: " . $order->info['order_id']);
        } else {
            $order->status = 'Pending';
            error_log("Payment failed for Order ID: " . $order->info['order_id']);
        }
    }

    function isOnline() {
        return true;
    }

    public function configure_keys() {
        return [
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_STATUS' => [
                'title' => 'Enable PaymentOscommerce Payment gateway Module ',
                'value' => 'True',
                'sort_order' => '1',
                'set_function' => 'tep_cfg_select_option(array(\'True\', \'False\'), ',
            ],
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_MERCHANT_ID' => [
                'title' => 'Merchant ID',
                'value' => '',
                'sort_order' => '2',
                'required' => true,
            ],
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_PARTNER_NAME' => [
                'title' => 'Partner Name',
                'value' => '',
                'sort_order' => '3',
                'required' => true,
            ],
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_SECRET_KEY' => [
                'title' => 'Secret Key',
                'value' => '',
                'sort_order' => '4',
                'required' => true,
            ],
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_REDIRECT_URL' => [
                'title' => 'Redirect Url',
                'value' => '',
                'sort_order' => '5',
                'required' => true,
            ],
            'MODULE_PAYMENT_PAYMENTOSCOMMERCE_TEST_URL' => [
                'title' => 'Live/Test URL',
                'value' => '',
                'sort_order' => '6',
                'required' => true,
            ],
        ];
    }

    public function describe_status_key() {
        return new ModuleStatus('MODULE_PAYMENT_PAYMENTOSCOMMERCE_STATUS', 'True', 'False');
    }

    public function describe_sort_key() {
        return new ModuleSortOrder('MODULE_PAYMENT_PAYMENTOSCOMMERCE_SORT_ORDER');
    }
}
