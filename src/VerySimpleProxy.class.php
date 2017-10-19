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
 	    $requestUri = $_SERVER['REQUEST_URI'];
// 	    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
//	    echo '<pre>'.print_r($_SERVER, TRUE).'</pre>';
	}
//	if (!empty($_SERVER['QUERY_STRING'])) {
//	    $requestUri .= '?' . $_SERVER['QUERY_STRING'];
//	    echo $_SERVER['QUERY_STRING'];
//	    echo $requestUri;
//	}
	
        if (!empty($requestUri)) {
            $translatedUri .= $requestUri;
        }
        else {
            $translatedUri .= '/';
        }


        // Handle the client headers.
        $this->handleClientHeaders();


        // Make request.

        $res = file_get_contents ($translatedUri);

        // Replace e-pub to mobi links.
        $res = str_replace('<head>', '<head><!-- Global Site Tag (gtag.js) - Google Analytics --> <script async src="https://www.googletagmanager.com/gtag/js?id=UA-17928634-2"></script><script>window.dataLayer = window.dataLayer || [];function gtag(){dataLayer.push(arguments)};gtag("js", new Date());gtag("config", "UA-17928634-2");</script>', $res);
        
        $res = str_replace('<title>', '<title>Огледало - ', $res);

        $res = preg_replace('#"(\/book.*)\.epub"#', '$1.mobi', $res);
        $res = preg_replace('#"(\/text.*)\.epub"#', '$1.mobi', $res);
        $pattern = '/\<ul class="dl-list"\>.*(\<a.*class\=\"dl dl\-epub action\".*?\<\/a\>).*<\/ul\>/s';
        $replace = '$1';
        ini_set('display_errors',1);
        ini_set('display_startup_errors',1);
        error_reporting(-1);
        //echo $res;
        try {
        
        	$pattern = '#\<ul class=\"dl-list\"\>.*(\<a.*class\=\"dl dl\-epub action\".*?\<\/a\>).*<\/ul\>#s';
		$replacement = '$1';
		$limit = -1;
		$count = 0;
		$result = preg_replace ($pattern, $replacement, $res, $limit, $count);
		//echo "<xmp>$count</xmp>";

        	//$res = preg_replace($pattern, '$1', $res);
		//$res = preg_replace($pattern, $replace, $res);
	} catch (Exception $e) {
	    echo 'Caught exception: ',  $e->getMessage(), "\n";
	}

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
