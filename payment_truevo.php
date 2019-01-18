<?php
/**
 * --------------------------------------------------------------------------------
 * Payment Plugin - Truevo
 * --------------------------------------------------------------------------------
 * @package     Joomla 2.5 -  3.x
 * @subpackage  J2 Store
 * @author      Saul Morales Pacheco <info@saulmoralespa.com>
 * @copyright   Saul Morales Pacheco 2018.
 * @license     GNU/GPL license: http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://saulmoralespa.com
 * --------------------------------------------------------------------------------
 * */

// No direct access

defined('_JEXEC') or die('Restricted access');

require_once JPATH_ADMINISTRATOR . '/components/com_j2store/library/plugins/payment.php';
require_once JPATH_ADMINISTRATOR . '/components/com_j2store/helpers/j2store.php';
require (JPath::clean ( dirname ( __FILE__ ) . "/library/truevo-api-php/autoload.php" ));

use Truevo\Truevo;

class plgJ2StorePayment_truevo extends J2StorePaymentPlugin
{
    public $_element = 'payment_truevo';
    protected $_user_login;
    protected $_user_password;
    protected $_entity_id;
    public $code_arr = array();
    private $_isLog = true;
    var $_j2version = null;

    /**
     * plgJ2StorePayment_truevo constructor.
     * @param $subject
     * @param $config
     */
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
        $this->loadLanguage('', JPATH_ADMINISTRATOR);

        $mode = $this->params->get('sandbox', 0);
        if(!$mode) {
            $this->_user_login = trim($this->params->get('truevo_user_login'));
            $this->_user_password = trim($this->params->get('truevo_user_password'));
            $this->_entity_id = trim($this->params->get('truevo_entity_id'));
        } else {
            $this->_user_login = trim($this->params->get('truevo_test_user_login'));
            $this->_user_password = trim($this->params->get('truevo_test_user_password'));
            $this->_entity_id = trim($this->params->get('truevo_test_entity_id'));
        }
    }

    /**
     * @param array $data
     * @return string
     */
    public function _renderForm($data)
    {
        $vars = new JObject();
        $vars->prepop = array();
        $vars->cctype_input   = $this->_cardTypesField();
        $vars->onselection_text = $this->params->get('onselection', '');
        $html = $this->_getLayout('form', $vars);

        return $html;
    }

    /**
     * @param array $submitted_values
     * @return JObject|obj
     */
    function _verifyForm( $submitted_values )
    {
        $object = new JObject();
        $object->error = false;
        $object->message = '';

        foreach ($submitted_values as $key=>$value)
        {
            switch ($key)
            {
                case "cardholder":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_HOLDER_NAME_REQUIRED" )."</li>";
                    }
                    break;
                case "cardnum":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_NUMBER_INVALID" )."</li>";
                    }
                    break;
                case "month":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID" )."</li>";
                    }
                    break;
                case "year":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_EXPIRATION_DATE_INVALID" )."</li>";
                    }
                    break;
                case "cardcvv":
                    if (!isset($submitted_values[$key]) || !JString::strlen($submitted_values[$key]))
                    {
                        $object->error = true;
                        $object->message .= "<li>".JText::_( "J2STORE_SAGEPAY_MESSAGE_CARD_CVV_INVALID" )."</li>";
                    }
                    break;
                default:
                    break;
            }
        }

        return $object;
    }

    function _cardTypesField( $field='cardtype', $default='', $options='' )
    {
        $types = array();
        $card_types = $this->params->get('card_types', array('VISA', 'MASTERCARD'));
        if(!is_array($card_types) ) {
            $card_types = array('VISA', 'MASTERCARD');
        }
        foreach($card_types as $type) {
            $types[] = JHTML::_('select.option', $type, JText::_( "J2STORE_TRUEVO_".strtoupper($type) ) );
        }

        $return = JHTML::_('select.genericlist', $types, $field, $options, 'value','text', $default);
        return $return;
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function _prePayment( $data )
    {
        $app = JFactory::getApplication();
        $currency = J2Store::currency();

        // Prepare the payment form
        $vars = new JObject;


        $vars->url = JRoute::_("index.php?option=com_j2store&view=checkout");
        $vars->order_id = $data['order_id'];
        $vars->orderpayment_id = $data['orderpayment_id'];
        $vars->orderpayment_type = $this->_element;

        F0FTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2store/tables');
        $order = F0FTable::getInstance('Order', 'J2StoreTable');
        $order->load($data['orderpayment_id']);

        $currency_values= $this->getCurrency($order);
        $amount = J2Store::currency()->format($order->order_total, $currency_values['currency_code'], $currency_values['currency_value'], false);
        $vars->amount = $amount*100;
        $vars->currency_code =$currency_values['currency_code'];


        $vars->cardholder = $app->input->getString("cardholder");
        $vars->cardtype = $app->input->getString("cardtype");
        $vars->cardnum = $app->input->getString("cardnum");
        $vars->cardmonth = $app->input->getString("month");
        $vars->cardyear = $app->input->getString("year");
        $vars->cardcvv = $app->input->getString("cardcvv");
        $vars->cardnum_last4 = substr( $vars->cardnum, -4 );


        $vars->display_name = $this->params->get('display_name', 'PLG_J2STORE_PAYMENT_PAYMILL');
        $vars->onbeforepayment_text = $this->params->get('onbeforepayment', '');
        $vars->button_text = $this->params->get('button_text', 'J2STORE_PLACE_ORDER');
        $vars->sandbox = $this->params->get('sandbox', 0);
        // Lets check the values submitted
        $html = $this->_getLayout('prepayment', $vars);

        return $html;
    }

    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function _postPayment( $data )
    {
        // Process the payment
        $app = JFactory::getApplication();
        $vars = new JObject();
        $paction = $app->input->getString('paction');

        switch ($paction)
        {
            case 'process_sofort':
                $this->_process_sofort();
                break;
            case 'cancel':
                $vars->message = JText::_($this->params->get('oncancelpayment', ''));
                $html = $this->_getLayout('message', $vars);
                break;
            case 'display':
                $html = JText::_($this->params->get('onafterpayment', ''));
                $html .= $this->_displayArticle();
                break;
            case 'process':
                $result = $this->_process();
                echo json_encode($result);
                $app->close();
                break;
            default:
                $vars->message = JText::_($this->params->get('onerrorpayment', ''));
                $html = $this->_getLayout('message', $vars);
                break;
        }

        return $html;
    }

    public function _process_sofort()
    {
        $body = @file_get_contents('php://input');
        $data = json_decode($body);
        http_response_code(200); // Return 200 OK
        $this->_log ( print_r($data, true), 'payment notify' );

    }

    /**
     * @return array
     * @throws Exception
     */
    public function _process()
    {
        if (! JRequest::checkToken ()) {
            return $this->_renderHtml ( JText::_ ( 'J2STORE_TRUEVO_INVALID_TOKEN' ) );
        }

        $app = JFactory::getApplication ();
        $data = $app->input->getArray( $_POST );
        $json = array();
        $errors = array();

        // Get order information
        F0FTable::addIncludePath ( JPATH_ADMINISTRATOR . '/components/com_j2store/tables' );
        $order = F0FTable::getInstance ( 'Order', 'J2StoreTable' );
        if ($order->load ( array (
            'order_id' => $data ['order_id']
        ) )) {

            $currency_values = $this->getCurrency ( $order );
            $amount = J2Store::currency()->format($order->order_total, $currency_values['currency_code'], $currency_values['currency_value'], false);
            $orderinfo = $order->getOrderInformation();
            $nameClient = empty($orderinfo->shipping_first_name) ? $orderinfo->billing_first_name : $orderinfo->shipping_first_name;
            $surname = empty($orderinfo->shipping_last_name) ? $orderinfo->billing_last_name : $orderinfo->shipping_last_name;
            $phone = empty($orderinfo->shipping_phone_2) ? $orderinfo->billing_phone_2 : $orderinfo->shipping_phone_2;
            $address1 = empty($orderinfo->shipping_address_1) ? $orderinfo->billing_address_1 : $orderinfo->shipping_address_1;
            $address2 = empty($orderinfo->shipping_address_2) ? $orderinfo->billing_address_2 : $orderinfo->shipping_address_2;
            $city = empty($orderinfo->shipping_city) ? $orderinfo->billing_city : $orderinfo->shipping_city;
            $postcode = empty($orderinfo->shipping_zip) ? $orderinfo->billing_zip : $orderinfo->shipping_zip;

            if (empty($orderinfo->shipping_zone_id)){
                $state = substr($this->getZoneById($orderinfo->billing_zone_id)->zone_code, 0, 2);
            }else{
                $state = substr($this->getZoneById($orderinfo->shipping_zone_id)->zone_code, 0, 2);
            }

            if (empty($orderinfo->shipping_country_id)){
                $this->getCountryById($orderinfo->billing_country_id)->country_isocode_2;
            }else{
                $country = $this->getCountryById($orderinfo->shipping_country_id)->country_isocode_2;
            }


            $truevo = new Truevo($this->_user_login, $this->_user_password, $this->_entity_id);
            $sandbox = (bool)(int)$data['sandbox'];
            $truevo->sandbox_mode($sandbox);

            $notificationUrl = JURI::root() . 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=payment_truevo&paction=process_sofort';

            $params = array(
                'amount' => $amount,
                'currency' => $currency_values['currency_code'],
                'paymentBrand' => $data ['cardtype'],
                'paymentType' => 'DB',//
                'card.number' => $data ['cardnum'],
                'card.holder' => $data ['cardholder'],
                'card.expiryMonth' => $data ['cardmonth'],
                'card.expiryYear' => $data ['cardyear'],
                'card.cvv' => $data['cardcvv'],
                'customer.givenName' => $nameClient,
                'customer.surname' => $surname,
                'customer.mobile' => $phone,
                'customer.email' => $order->user_email,
                'billing.street1' => $address1,
                'billing.street2' => $address2,
                'billing.city' => $city,
                'billing.state' => $state,
                'billing.postcode' => $postcode,
                'billing.country' => $country,
                'notificationUrl' => urldecode($notificationUrl)
            );

            try{
                $data = $truevo->payment($params);
                $this->_log ( print_r($data, true), 'payment response' );

                if (in_array($data->result->code,$truevo->getCodesSuccessfully())){
                    $order->payment_complete();
                }elseif (in_array($data->result->code, $truevo->getCodesPending())){
                    $order->update_status ( $data->result->description);
                }

            }catch (\Truevo\TruevoException $exception){
                $msg = $exception->getMessage();
                $errors[] = $msg;
                $this->_log ( $msg, 'payment response error' );
            }

            if (empty ( $errors )) {
                $json ['success'] = JText::_ ( $this->params->get ( 'onafterpayment', '' ) );
                $json ['redirect'] = JRoute::_ ( 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=' . $this->_element . '&paction=display' );
            }

            if (count ( $errors )) {
                $json ['error'] = implode ( "\n", $errors );
            }
        }else {
            $json ['error'] = JText::_ ( 'J2STORE_TRUEVO_INVALID_ORDER' );
        }

        return $json;

    }

    public function _log($text, $type = 'message')
    {
        if ($this->_isLog)
            :
            {
                $file = JPATH_ROOT . "/cache/{$this->_element}.log";
                $date = JFactory::getDate();

                $f = fopen($file, 'a');
                fwrite($f, "\n\n" . $date->format('Y-m-d H:i:s'));
                fwrite($f, "\n" . $type . ': ' . $text);
                fclose($f);
            }
        endif;
    }
}