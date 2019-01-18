<?php

namespace Truevo;

/**
 *  @author Saul Morales Pacheco <info@saulmoralespa.com>
 *
 */
class Truevo
{

    const ENVIROMENT_HOST = 'https://swishme.eu/';

    const ENVIROMENT_TEST_HOST = 'https://test.swishme.eu/';

    const VERSION_API = 'v1';

    protected $_user_login;

    protected $_user_password;

    protected $_entity_id;

    public $sandbox = false;

    /**
     * Truevo constructor.
     * @param $_user_login
     * @param $_user_password
     * @param $_entity_id
     */
    public function __construct($_user_login, $_user_password, $_entity_id)
    {
        $this->_user_login = $_user_login;
        $this->_user_password = $_user_password;
        $this->_entity_id = $_entity_id;
    }

    /**
     * @param bool $status
     * @return $this
     */
    public function sandbox_mode($status = false)
    {
        if($status){
            $this->sandbox = true;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getUrlBase()
    {
        $url = $this->sandbox  ? self::ENVIROMENT_TEST_HOST : self::ENVIROMENT_HOST;

        return $url;
    }

    /**
     * @return string
     */
    public function getUrlPayment()
    {
        $url = $this->getUrlBase() . self::VERSION_API . '/payments';
        return $url;

    }

    /**
     * @param $params
     * @return bool|string
     * @throws TruevoException
     */
    public function payment($params)
    {
        $data = array_merge($this->_paramsAccess(), $params, $this->testModeConnector());
        $request = array('data' => $data, 'url' => $this->getUrlPayment());

        return $this->_exec($request);

    }

    public function testModeConnector()
    {
        return array(
            'testMode' =>  $this->sandbox ? 'EXTERNAL' : 'INTERNAL'
        );
    }


    /**
     * @param $id
     * @return bool|string
     * @throws TruevoException
     */
    public function getPaymentStatus($id)
    {
        return $this->_exec($id);
    }

    /**
     * @return array
     */
    protected function _paramsAccess()
    {
        return array(
            'authentication.userId' => $this->_user_login,
            'authentication.password' => $this->_user_password,
            'authentication.entityId' => $this->_entity_id
        );
    }

    public function resultCodesSuccessfully()
    {
        return array(
            '000.000.000',
            '000.000.100',
            '000.100.110',
            '000.100.111',
            '000.100.112',
            '000.300.000',
            '000.300.100',
            '000.300.101',
            '000.300.102',
            '000.600.000'
        );
    }

    public function resultCodesPending()
    {
        return array(
            '000.200.000',
            '000.200.100',
            '000.200.101',
            '000.200.102',
            '000.200.200'
        );
    }

    /**
     * @param $request
     * @return bool|string
     * @throws TruevoException
     */
    private function _exec($request)
    {
        $connect = $this->_buildRequest($request);

        $api_result = curl_exec($connect);

        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_result === false) {
            throw new  TruevoException(curl_error($connect));
        }

        $response = json_decode($api_result);

        if ($api_http_code !== 200){
            if (isset($response->result)){
                $description =$response->result->description;
                $code_error = $response->result->code;
                throw new  TruevoException("$description code: $code_error", $api_http_code);
            }
        }

        curl_close($connect);

        return $response;

    }


    private function _buildRequest($request)
    {
        $ch = curl_init();

        if (is_array($request)){
            curl_setopt($ch, CURLOPT_URL, $request['url']);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($request['data']));
        }else{
            $urlStatus = $this->getUrlPayment() . DIRECTORY_SEPARATOR . $request . "?" . http_build_query($this->_paramsAccess());
            curl_setopt($ch, CURLOPT_URL, $urlStatus);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->sandbox ? false : true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        return $ch;

    }
}