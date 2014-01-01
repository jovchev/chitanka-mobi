<?php


class VerySimpleProxy
{
    private $_clientHeaders = array();

    function __construct($options)
    {
        if (is_string($options)) {
            $options = array('proxyUri' => $options);
        }
        // trim slashes, we will append what is needed later
        $translatedUri = rtrim($options['proxyUri'], '/');

        // Get all parameters from options
        $requestUri = '';
	if (!empty($_SERVER['REQUEST_URI'])) {
 	    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	}
	if (!empty($_SERVER['QUERY_STRING'])) {
	    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
	}
	
        if (!empty($requestUri)) {
            $translatedUri .= $requestUri;
        }
        else {
            $translatedUri .= '/';
        }


        // Handle the client headers.
        $this->handleClientHeaders();
        $res = file_get_contents ($translatedUri);
        $res = preg_replace('/\/book\/.*\.epub"/', '/conv.php?$0', $res);
        $res = preg_replace('/\/text\/.*\.epub"/', '/conv.php?$0', $res);

        echo $res;

    }


    private function _getRequestHeaders()
    {
        if (function_exists('apache_request_headers')) {
            if ($headers = apache_request_headers()) {
                return $headers;
            }
        }

        $headers = array();
        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) == 'HTTP_' && !empty($value)) {
                $headerName = strtolower(substr($key, 5, strlen($key)));
                $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', $headerName)));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    protected function handleClientHeaders()
    {
        $headers = $this->_getRequestHeaders();
        $xForwardedFor = array();

        foreach ($headers as $headerName => $value) {
            switch($headerName) {
                case 'Host':
                case 'X-Real-IP':
                    break;
                case 'X-Forwarded-For':
                    $xForwardedFor[] = $value;
                    break;
                default:
                    $this->setClientHeader($headerName, $value);
                    break;
            }
        }

        $xForwardedFor[] = $_SERVER['REMOTE_ADDR'];
        $this->setClientHeader('X-Forwarded-For', implode(',', $xForwardedFor));
        $this->setClientHeader('X-Real-IP', $xForwardedFor[0]);
    }


    public function setClientHeader($headerName, $value)
    {
        $this->_clientHeaders[] = $headerName . ': ' . $value;
    }


}