<?php

/*
 * Flow API
 *
 * Version: 1.4
 * Date:    2015-05-25
 * Author:  flow.cl
 */

namespace CokeCancino\LaravelFlow;

use Exception;

class Flow {

    protected $order = array();

    //Método constructor de compatibilidad
    function flowAPI() {;
        $this->__construct();
    }

    //Constructor de la clase
    function __construct() {
        //global $flow_medioPago;
        $this->secretKey = config('flow.secret_key');
        $this->order["OrdenNumero"] = "";
        $this->order["Concepto"] = "";
        $this->order["Monto"] = "";
        $this->order["MedioPago"] = config('flow.medioPago');
        $this->order["FlowNumero"] = "";
        $this->order["Pagador"] = "";
        $this->order["Status"] = "";
        $this->order["Error"] = "";
        $this->order["Optionals"] = "";
    }

    // Metodos SET

    /**
     * Set el número de Orden del comercio
     *
     * @param string $orderNumer El número de la Orden del Comercio
     *
     * @return bool (true/false)
     */
    public function setOrderNumber($orderNumber) {
        if(!empty($orderNumber)) {
            $this->order["OrdenNumero"] = $orderNumber;
        }
        $this->flow_log("Asigna Orden N°: ". $this->order["OrdenNumero"], '');
        return !empty($orderNumber);
    }

    /**
     * Set el concepto de pago
     *
     * @param string $concepto El concepto del pago
     *
     * @return bool (true/false)
     */
    public function setConcept($concepto) {
        if(!empty($concepto)) {
            $this->order["Concepto"] = $concepto;
        }
        return !empty($concepto);
    }

    /**
     * Set el monto del pago
     *
     * @param string $monto El monto del pago
     *
     * @return bool (true/false)
     */
    public function setAmount($monto) {
        if(!empty($monto)) {
            $this->order["Monto"] = $monto;
        }
        return !empty($monto);
    }

    /**
     * Set Medio de Pago, por default el Medio de Pago será el configurada en config.php
     *
     * @param string $medio El Medio de Pago de esta orden
     *
     * @return bool (true/false)
     */
    public function setMedio($medio) {
        if(!empty($medio)) {
            $this->order["MedioPago"] = $medio;
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * Set pagador, el email del pagador de esta orden
     *
     * @param string $email El email del pagador de la orden
     *
     * @return bool (true/false)
     */
    public function setPagador($email) {
        if(!empty($email)) {
            $this->order["Pagador"] = $email;
            return TRUE;
        } else {
            return FALSE;
        }
    }


    // Metodos GET

    /**
     * Get el número de Orden del Comercio
     *
     * @return string el número de Orden del comercio
     */
    public function getOrderNumber() {
        return $this->order["OrdenNumero"];
    }

    /**
     * Get el concepto de Orden del Comercio
     *
     * @return string el concepto de Orden del comercio
     */
    public function getConcept() {
        return $this->order["Concepto"];
    }

    /**
     * Get el monto de Orden del Comercio
     *
     * @return string el monto de la Orden del comercio
     */
    public function getAmount() {
        return $this->order["Monto"];
    }

    /**
     * Get el Medio de Pago para de Orden del Comercio
     *
     * @return string el Medio de pago de esta Orden del comercio
     */
    public function getMedio() {
        return $this->order["MedioPago"];
    }

    /**
     * Get el estado de la Orden del Comercio
     *
     * @return string el estado de la Orden del comercio
     */
    public function getStatus() {
        return $this->order["Status"];
    }

    /**
     * Get el número de Orden de Flow
     *
     * @return string el número de la Orden de Flow
     */
    public function getFlowNumber() {
        return $this->order["FlowNumero"];
    }

    /**
     * Get el email del pagador de la Orden
     *
     * @return string el email del pagador de la Orden de Flow
     */
    public function getPayer() {
        return $this->order["Pagador"];
    }


    /**
     * Crea una nueva Orden para ser enviada a Flow
     *
     * @param string $orden_compra El número de Orden de Compra del Comercio
     * @param string $monto El monto de Orden de Compra del Comercio
     * @param string $concepto El concepto de Orden de Compra del Comercio
     * @param string $email_pagador El email del pagador de Orden de Compra del Comercio
     * @param mixed $medioPago El Medio de Pago (1,2,9)
     *
     * @return string flow_pack Paquete de datos firmados listos para ser enviados a Flow
     */
    public function newOrder($orden_compra, $monto,  $concepto, $email_pagador, $optionals = [], $medioPago = "Non") {
        $this->flow_log("Iniciando nueva Orden", "new_order");
        if(!isset($orden_compra,$monto,$concepto)) {
            $this->flow_log("Error: No se pasaron todos los parámetros obligatorios","new_order");
        }
        if($medioPago == "Non") {
            $medioPago = config('flow.medioPago');
        }
        if(!is_numeric($monto)) {
            $this->flow_log("Error: El parámetro monto de la orden debe ser numérico","new_order");
            throw new Exception("El monto de la orden debe ser numérico");
        }
        $this->order["OrdenNumero"] = $orden_compra;
        $this->order["Concepto"] = $concepto;
        $this->order["Monto"] = $monto;
        $this->order["MedioPago"] = $medioPago;
        $this->order["Pagador"] = $email_pagador;
        $this->order["Optionals"] = $optionals;

        return $this->setParamsAndSign();
    }

    /**
     * Hace la llamada a flow para crear la orden y obtener la url de pago
     *
     * @param string $params Parametros firmados
     *
     */
    public function createFlowOrder($signedParams) {
        $url = config('flow.url_pago');
        $response = $this->httpPost($url, $signedParams);
        $data = json_decode($response["output"], true);
        return $data;
    }

    /**
     * Registra en el Log de Flow
     *
     * @param string $message El mensaje a ser escrito en el log
     * @param string $type Identificador del mensaje
     *
     */
    public function flow_log($message, $type) {
        //global $flow_logPath;
        $file = fopen(config('flow.logPath') . "/flowLog_" . date("Y-m-d") .".txt" , "a+");
        fwrite($file, "[".date("Y-m-d H:i:s.u")." ".getenv('REMOTE_ADDR')." ".getenv('HTTP_X_FORWARDED_FOR')." - $type ] ".$message . PHP_EOL);
        fclose($file);
    }

    /**
     * Funcion que firma los parametros
     * @param string $params Parametros a firmar
     * @return string de firma
     * @throws Exception
     */
    private function signParams($params) {
        $keys = array_keys($params);
        sort($keys);
        $toSign = "";
        foreach ($keys as $key) {
            $toSign .= $key . $params[$key];
        }
        if(!function_exists("hash_hmac")) {
            throw new Exception("function hash_hmac not exist", 1);
        }
        return hash_hmac('sha256', $toSign , $this->secretKey);
    }

    private function setNewOrderParamsAndSign() {
        $api_key = config('flow.api_key');
        $orden_compra = $this->order["OrdenNumero"];
        $monto = $this->order["Monto"];
        $medioPago = $this->order["MedioPago"];
        $email = $this->order["Pagador"];
        $concepto = $this->order["Concepto"];
        $optionals = json_encode($this->order["Optionals"]);
        $url_confirmacion = $this->generarUrl(config('flow.url_confirmacion'));
        $url_retorno = $this->generarUrl(config('flow.url_retorno'));

        $params = array(
            "apiKey" => $api_key,
            "commerceOrder" => $orden_compra,
            "subject" => $concepto,
            "currency" => "CLP",
            "amount" => $monto,
            "email" => $email,
            "paymentMethod" => $medioPago,
            "urlConfirmation" => $url_confirmacion,
            "urlReturn" => $url_retorno,
            "optional" => $optionals
        );

        $params["s"] = $this->signParams($params);
        return $params;
    }

    private function setGetOrderStatusParamsAndSign($flowOrderID) {
        $api_key = config('flow.api_key');
        $params = array(
            "apiKey" => $api_key,
            "flowOrder" => $flowOrderID
        );

        $params["s"] = $this->signParams($params);
        return $params;
    }

    /**
     * Funcion que hace el llamado via http POST
     * @param string $url url a invocar
     * @param array $params los datos a enviar
     * @return array el resultado de la llamada
     * @throws Exception
     */
    private function httpPost($url, $params) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        $output = curl_exec($ch);
        if($output === false) {
            $error = curl_error($ch);
            throw new Exception($error, 1);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array("output" =>$output, "info" => $info);
    }
    
    /**
     * Funcion que hace el llamado via http GET
     * @param string $url url a invocar
     * @param array $params los datos a enviar
     * @return array el resultado de la llamada
     * @throws Exception
     */
    private function httpGet($url, $params) {
        $url = $url . "?" . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $output = curl_exec($ch);
        if($output === false) {
            $error = curl_error($ch);
            throw new Exception($error, 1);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        return array("output" =>$output, "info" => $info);
    }

    /**
     * Genera una URL utilizando las funciones de Laravel
     *
     * @param  mixed  $url
     * @return string
     */
    private function generarUrl($url)
    {
        if (is_array($url)) {
            if (array_key_exists('url', $url))
                return url($url['url']);

            if (array_key_exists('route', $url))
                return route($url['route']);

            if (array_key_exists('action', $url))
                return action($url['action']);

            return '';
        } else {
            return $url;
        }
    }
}
