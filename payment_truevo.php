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
    public $urlWidget;
    public $sandbox;
    public $code_arr = array();
    private $_isLog = false;
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
        $this->sandbox = (bool)(int)$this->params->get('sandbox', 0);
        if(!$this->sandbox) {
            $this->urlWidget = 'https://swishme.eu/v1/paymentWidgets.js?checkoutId=';
            $this->_user_login = trim($this->params->get('truevo_user_login'));
            $this->_user_password = trim($this->params->get('truevo_user_password'));
            $this->_entity_id = trim($this->params->get('truevo_entity_id'));
        } else {
            $this->urlWidget = 'https://test.swishme.eu/v1/paymentWidgets.js?checkoutId=';
            $this->_user_login = trim($this->params->get('truevo_test_user_login'));
            $this->_user_password = trim($this->params->get('truevo_test_user_password'));
            $this->_entity_id = trim($this->params->get('truevo_test_entity_id'));
        }

        if($this->params->get('debug', 0)) {
            $this->_isLog = true;
        }
    }
    /**
     * @param array $data
     * @return string
     */
    public function _renderForm($data)
    {
        $vars = new JObject();
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

    function _cardTypesField()
    {
        $card_types = $this->params->get('card_types', array('VISA', 'MASTER', 'AMEX'));
        if(!is_array($card_types) ) {
            $card_types = array('VISA', 'MASTER', 'AMEX');
        }

        return $card_types;
    }
    /**
     * @param array $data
     * @return string
     * @throws Exception
     */
    public function _prePayment( $data )
    {

        $this->_log( print_r($data, true), 'data received prepayment' );
        $app = JFactory::getApplication();
        $currency = J2Store::currency();
        // Prepare the payment form
        $vars = new JObject;
        $vars->url = JRoute::_("index.php?option=com_j2store&view=checkout");
        $vars->urlWidget = $this->urlWidget;
        $order_id = $data['order_id'];
        $vars->shopperResultUrl = JURI::root() . "index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=payment_truevo&paction=process_sofort&order_id=$order_id";
        $vars->orderpayment_id = $data['orderpayment_id'];
        $vars->orderpayment_type = $this->_element;
        F0FTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_j2store/tables');
        $order = F0FTable::getInstance('Order', 'J2StoreTable');
        $order->load($data['orderpayment_id']);
        $currency_values= $this->getCurrency($order);
        $amount = J2Store::currency()->format($order->order_total, $currency_values['currency_code'], $currency_values['currency_value'], false);
        $vars->amount = $amount;
        $currency = $currency_values['currency_code'];
        $vars->currency_code = $currency_values['currency_code'];
        $vars->cctypes = $this->_cardTypesField();
        $truevo = new Truevo($this->_user_login, $this->_user_password, $this->_entity_id);
        $truevo->sandbox_mode($this->sandbox);

        $params = array(
            'amount' => $amount,
            'currency' => $currency,
            'paymentType' => 'DB'
        );

        try{
            $data = $truevo->checkout($params);
            $vars->checkout_id = $data->id;
        }catch (\Truevo\TruevoException $exception){
            $this->_log ( $exception->getMessage(), 'error prepare the checkout' );
        }

        $vars->display_name = $this->params->get('display_name', 'PLG_J2STORE_PAYMENT_TRUEVO');
        $vars->onbeforepayment_text = $this->params->get('onbeforepayment', '');
        $vars->button_text = $this->params->get('button_text', 'J2STORE_PLACE_ORDER');
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
        $app = JFactory::getApplication();
        $data = $app->input->getArray($_REQUEST);

        F0FTable::addIncludePath ( JPATH_ADMINISTRATOR . '/components/com_j2store/tables' );
        $order = F0FTable::getInstance ( 'Order', 'J2StoreTable' );


        if (isset($data['id']) && isset($data['order_id'])){
            $id = $data['id'];
            $order_id = $data['order_id'];

            $truevo = new Truevo($this->_user_login, $this->_user_password, $this->_entity_id);
            $truevo->sandbox_mode($this->sandbox);


            if ($order->load ( array (
                'order_id' => $order_id
            ) )) {

                try{
                    $data = $truevo->getCheckoutStatus($id);

                    if (in_array($data->result->code, $truevo->getCodesSuccessfully())){
                        $order->payment_complete();
                    }elseif (in_array($data->result->code, $truevo->getCodesPending())){
                        $order->update_status ( $data->result->description);
                    }

                    $redirect = JRoute::_ ( 'index.php?option=com_j2store&view=checkout&task=confirmPayment&orderpayment_type=' . $this->_element . '&paction=display' );

                    $app->redirect($redirect);

                }catch (\Truevo\TruevoException $exception){
                    $this->_log( $exception->getMessage(), 'error prepare the checkout' );
                }
            }

        }
    }
    /**
     * @return array
     * @throws Exception
     */
    public function _process()
    {

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