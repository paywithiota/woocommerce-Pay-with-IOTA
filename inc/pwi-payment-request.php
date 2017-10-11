<?php

if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to Pay with IOTA.
 */
class PWI_Payment_Request
{

    /**
     * Stores line items to send to PayPal.
     * @var array
     */
    protected $line_items = array();

    /**
     * Pointer to gateway making the request.
     * @var WC_Gateway_Paypal
     */
    protected $gateway;

    /**
     * Constructor.
     *
     * @param WC_Gateway_Paypal $gateway
     */
    public function __construct($gateway)
    {

        $this->gateway = $gateway;
    }

    public function create_payment_id()
    {

        $paymentId = null;

        $data = [
            'invoice_id'      => $this->gateway->order->get_order_number(),
            'price'           => $this->gateway->order->get_total(),
            'currency'        => $this->gateway->order->get_currency(),
            'ipn'             => site_url('?pay-with-iota-ipn=1'),
            'ipn_verify_code' => $this->gateway->ipn_verify_code
        ];
        $parameterForPayment = [
            'METHOD' => 'POST',
            'URL'    => rtrim($this->gateway->api_host, '/') . '/' . rtrim(ltrim($this->gateway->api_path, '/'),
                    '/') . '/payments?api_token=' . $this->gateway->api_token,
            'DATA'   => $data
        ];

        try{
            $response = $this->call($parameterForPayment);

            if ($response->status == 1) {
                $paymentId = isset($response->data->payment_id) ? $response->data->payment_id : null;

            }

            return $paymentId;

        }catch (Exception $e){
            return $paymentId;
        }
    }


    /**
     * Call with curl
     *
     * @param array $userData
     *
     * @return mixed|stdClass
     * @throws Exception
     */
    private function call($userData = [])
    {
        if (is_string($userData)) {
            $userData = ['URL' => $userData];
        }

        $request = array_merge([
            'CHARSET'     => 'UTF-8',
            'METHOD'      => 'GET',
            'URL'         => '/',
            'HEADERS'     => array(),
            'DATA'        => array(),
            'FAILONERROR' => false,
            'RETURNARRAY' => false,
            'ALLDATA'     => false
        ], $userData);

        // Send & accept JSON data
        $defaultHeaders = array();
        $defaultHeaders[] = 'Content-Type: application/json; charset=' . $request['CHARSET'];
        $defaultHeaders[] = 'Accept: application/json';

        $headers = array_merge($defaultHeaders, $request['HEADERS']);

        $url = $request['URL'];


        // cURL setup
        $ch = curl_init();
        $options = array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => $url,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => strtoupper($request['METHOD']),
            CURLOPT_ENCODING       => '',
            CURLOPT_USERAGENT      => 'PWI/WC',
            CURLOPT_FAILONERROR    => $request['FAILONERROR'],
            CURLOPT_VERBOSE        => $request['ALLDATA'],
            CURLOPT_HEADER         => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        );

        // Checks if DATA is being sent
        if ( ! empty($request['DATA'])) {
            if (is_array($request['DATA'])) {
                $options[CURLOPT_POSTFIELDS] = json_encode($request['DATA']);
            }else {
                // Detect if already a JSON object
                json_decode($request['DATA']);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $options[CURLOPT_POSTFIELDS] = $request['DATA'];
                }else {
                    throw new \Exception('DATA malformed.');
                }
            }
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // Data returned
        $result = json_decode(substr($response, $headerSize), $request['RETURNARRAY']);

        // Headers
        $info = array_filter(array_map('trim', explode("\n", substr($response, 0, $headerSize))));

        foreach ($info as $k => $header) {
            if (strpos($header, 'HTTP/') > -1) {
                $_INFO['HTTP_CODE'] = $header;
                continue;
            }

            list($key, $val) = explode(':', $header);
            $_INFO[trim($key)] = trim($val);
        }


        // cURL Errors
        $_ERROR = array('NUMBER' => curl_errno($ch), 'MESSAGE' => curl_error($ch));

        curl_close($ch);

        if ($_ERROR['NUMBER']) {
            throw new \Exception('ERROR #' . $_ERROR['NUMBER'] . ': ' . $_ERROR['MESSAGE']);
        }

        // Send back in format that user requested
        if ($request['ALLDATA']) {
            if ($request['RETURNARRAY']) {
                $result['_ERROR'] = $_ERROR;
            }else {
                $result = $result ? $result : new \stdClass();
                $result->_ERROR = $_ERROR;
            }

            return $result;
        }else {
            return $result;
        }
    }


}
