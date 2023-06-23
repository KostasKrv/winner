<?php
namespace WinnerApp;

class JsonResponse
{
    public $responseCode;
    public $errorsArray = array();
    public $dataArray = array();

    function hasErrors()
    {
        if ($this->errorsArray !== null && is_array($this->errorsArray) && !empty($this->errorsArray)) {
            return true;
        }

        return false;
    }

    function handleError($error)
    {
        if ($this->errorsArray === null || !is_array($this->errorsArray)) {
            $this->errorsArray = array();
        }

        if (!empty($error)) {
            $this->errorsArray[] = $error;
        }

        return $this;
    }

    function toJsonResponse()
    {
        header("Content-Type: text/json; charset=utf-8");
        //ini_set('display_errors',1);
        //error_reporting(E_ALL);

        /// Change the response code maybe
        $resp_code = 200;
        if ($this->hasErrors()) {
            $resp_code = 406;
        }

        ob_end_clean();
        header("Content-Type: text/json; charset=utf-8");
        http_response_code($resp_code);

        /// Remove debug data if not necessary
        $filtered_data = array();
        $debug_data = array();

        foreach ($this->dataArray as $k => $v) {
            $keyStartWithDebug = mb_substr($k, 0, 6) == 'debug-';
            if ($keyStartWithDebug) {
                $debug_data[str_replace('debug-', '', $k)] = $v;
            } else {
                $filtered_data[$k] = $v;
            }
        }

        $respArray = array(
            'data' => $filtered_data,
            'errors' => $this->errorsArray,
        );

        echo json_encode($respArray, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        exit;
    }

    /*public function isAjax() {
        if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {            
            return true;
        }

        return false;
    }

    public function requireAjax($halt = true)
    {        
        if (!$this->isAjax()) {
            $this->handleError('Ajax call is requested');

            if ($halt) {
                $this->toJsonResponse();
            }
        }

        return $this;
    }*/
}
