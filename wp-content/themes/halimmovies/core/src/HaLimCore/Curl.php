<?php


//https://github.com/lisgroup/curl-http
class HaLimCore_Curl
{
    /**
     * @var self instance
     */
    private static $instance;

    /**
     * Http constructor.
     * @param array $opts
     */
    public function __construct($opts = array())
    {
        foreach ($opts as $key => $o) {
            $this->$key = $o;
        }
    }

    /**
     * @param array $conf
     *
     * @return Http
     */
    public static function getInstent($conf = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new static($conf);
        }
        return self::$instance;
    }

    /**
     * GET
     *
     * @param $url
     * @param array $params
     * @param int $timeout
     * @param array $header
     * @param string $cookie
     *
     * @return array
     */
    public function get($url, $params = array(), $timeout = 8, $header = array(), $cookie = '')
    {
        return $this->request($url, $params, 'GET', $timeout, $header, $cookie);
    }

    /**
     * POST
     *
     * @param $url
     * @param array $params
     * @param int $timeout
     * @param array $header
     * @param string $cookie
     *
     * @return array
     */
    public function post($url, $params = array(), $timeout = 8, $header = array(), $cookie = '')
    {
        return $this->request($url, $params, 'POST', $timeout, $header, $cookie);
    }

    /**
     * request
     *
     * @param $url
     * @param array $params
     * @param string $method
     * @param int $timemOut
     * @param array $headers
     * @param string $cookie
     *
     * @return array
     */
    public function request($url, $params = array(), $method = "GET", $timemOut = 8, $headers = array(), $cookie = '')
    {
        $method = strtoupper($method);
        // New request method
        $methodArray = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

        if (!in_array($method, $methodArray)) {
            $method = "GET";
        }

        if ($params) {
            if (is_array($params)) {
                $paramsString = http_build_query($params);
            } else {
                $paramsString = $params;
            }
        } else {
            $paramsString = "";
        }

        // $tempUrl = $url;
        if ($method == "GET" && !empty($paramsString)) {
            $url = $url."?".$paramsString;
        }

        // curl_init
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timemOut);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (strtolower(substr($url, 0, 8)) == 'https://') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Skip check certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // VerifyHost no
        }

        // Request header
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        if (!empty($cookie)) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }

        // Request method
        switch ($method) {
            case 'GET':
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString); // Request body
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $paramsString); // Request body
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);

        // request time
        $timeStampBegin = microtime(true);
        //$timeBegin = date("Y-m-d H:i:s");
        $httpContent = curl_exec($ch);
        $timeStampEnd = microtime(true);
        // $timeEnd = date("Y-m-d H:i:s");

        $httpInfo = array();
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
        $curlErrNo = curl_errno($ch);
        $httpError = curl_error($ch);
        $httpCost = round($timeStampEnd - $timeStampBegin, 3);

        // close curl
        curl_close($ch);

        $curlErrMsg = $this->_curlErrNoMap($curlErrNo);

        return array(
            'httpCode' => $httpCode, // http code
            'error' => $httpError, // error message
            'curlErrno' => $curlErrNo, // curl error code,
            'curlErrMsg' => $curlErrMsg,
            'cost' => $httpCost, // Network execution time
            'content' => $httpContent, // return content
            'httpInfo' => $httpInfo
        );
    }

    public function _curlErrNoMap($curlErrNo)
    {
        $error_codes = array(
            '0' => 'CURLE_OK',
            '1' => 'CURLE_UNSUPPORTED_PROTOCOL',
            '2' => 'CURLE_FAILED_INIT',
            '3' => 'CURLE_URL_MALFORMAT',
            '4' => 'CURLE_URL_MALFORMAT_USER',
            '5' => 'CURLE_COULDNT_RESOLVE_PROXY',
            '6' => 'CURLE_COULDNT_RESOLVE_HOST',
            '7' => 'CURLE_COULDNT_CONNECT',
            '8' => 'CURLE_FTP_WEIRD_SERVER_REPLY',
            '9' => 'CURLE_REMOTE_ACCESS_DENIED',
            '11' => 'CURLE_FTP_WEIRD_PASS_REPLY',
            '13' => 'CURLE_FTP_WEIRD_PASV_REPLY',
            '14' => 'CURLE_FTP_WEIRD_227_FORMAT',
            '15' => 'CURLE_FTP_CANT_GET_HOST',
            '17' => 'CURLE_FTP_COULDNT_SET_TYPE',
            '18' => 'CURLE_PARTIAL_FILE',
            '19' => 'CURLE_FTP_COULDNT_RETR_FILE',
            '21' => 'CURLE_QUOTE_ERROR',
            '22' => 'CURLE_HTTP_RETURNED_ERROR',
            '23' => 'CURLE_WRITE_ERROR',
            '25' => 'CURLE_UPLOAD_FAILED',
            '26' => 'CURLE_READ_ERROR',
            '27' => 'CURLE_OUT_OF_MEMORY',
            '28' => 'CURLE_OPERATION_TIMEDOUT',
            '30' => 'CURLE_FTP_PORT_FAILED',
            '31' => 'CURLE_FTP_COULDNT_USE_REST',
            '33' => 'CURLE_RANGE_ERROR',
            '34' => 'CURLE_HTTP_POST_ERROR',
            '35' => 'CURLE_SSL_CONNECT_ERROR',
            '36' => 'CURLE_BAD_DOWNLOAD_RESUME',
            '37' => 'CURLE_FILE_COULDNT_READ_FILE',
            '38' => 'CURLE_LDAP_CANNOT_BIND',
            '39' => 'CURLE_LDAP_SEARCH_FAILED',
            '41' => 'CURLE_FUNCTION_NOT_FOUND',
            '42' => 'CURLE_ABORTED_BY_CALLBACK',
            '43' => 'CURLE_BAD_FUNCTION_ARGUMENT',
            '45' => 'CURLE_INTERFACE_FAILED',
            '47' => 'CURLE_TOO_MANY_REDIRECTS',
            '48' => 'CURLE_UNKNOWN_TELNET_OPTION',
            '49' => 'CURLE_TELNET_OPTION_SYNTAX',
            '51' => 'CURLE_PEER_FAILED_VERIFICATION',
            '52' => 'CURLE_GOT_NOTHING',
            '53' => 'CURLE_SSL_ENGINE_NOTFOUND',
            '54' => 'CURLE_SSL_ENGINE_SETFAILED',
            '55' => 'CURLE_SEND_ERROR',
            '56' => 'CURLE_RECV_ERROR',
            '58' => 'CURLE_SSL_CERTPROBLEM',
            '59' => 'CURLE_SSL_CIPHER',
            '60' => 'CURLE_SSL_CACERT',
            '61' => 'CURLE_BAD_CONTENT_ENCODING',
            '62' => 'CURLE_LDAP_INVALID_URL',
            '63' => 'CURLE_FILESIZE_EXCEEDED',
            '64' => 'CURLE_USE_SSL_FAILED',
            '65' => 'CURLE_SEND_FAIL_REWIND',
            '66' => 'CURLE_SSL_ENGINE_INITFAILED',
            '67' => 'CURLE_LOGIN_DENIED',
            '68' => 'CURLE_TFTP_NOTFOUND',
            '69' => 'CURLE_TFTP_PERM',
            '70' => 'CURLE_REMOTE_DISK_FULL',
            '71' => 'CURLE_TFTP_ILLEGAL',
            '72' => 'CURLE_TFTP_UNKNOWNID',
            '73' => 'CURLE_REMOTE_FILE_EXISTS',
            '74' => 'CURLE_TFTP_NOSUCHUSER',
            '75' => 'CURLE_CONV_FAILED',
            '76' => 'CURLE_CONV_REQD',
            '77' => 'CURLE_SSL_CACERT_BADFILE',
            '78' => 'CURLE_REMOTE_FILE_NOT_FOUND',
            '79' => 'CURLE_SSH',
            '80' => 'CURLE_SSL_SHUTDOWN_FAILED',
            '81' => 'CURLE_AGAIN',
            '82' => 'CURLE_SSL_CRL_BADFILE',
            '83' => 'CURLE_SSL_ISSUER_ERROR',
            '84' => 'CURLE_FTP_PRET_FAILED',
            '85' => 'CURLE_RTSP_CSEQ_ERROR',
            '86' => 'CURLE_RTSP_SESSION_ERROR',
            '87' => 'CURLE_FTP_BAD_FILE_LIST',
            '88' => 'CURLE_CHUNK_FAILED'
        );
        // $curlErrNo = (int) $curlErrNo;
        if (isset($error_codes[$curlErrNo])) {
            return $error_codes[$curlErrNo];
        } else {
            return "";
        }
    }
}

class HaLimCore_Curl_Old {
    private $ch;
    private $defaults = [
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_POST => 0,
        CURLOPT_FOLLOWLOCATION => 1
    ];

    public function __construct(array $options = []) {
        $this->ch = curl_init();

        $this->setOptions($options);
    }

    public function __destruct() {
        curl_close($this->ch);
    }


    public function get(string $url) {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_POST, 0);

        return $this->execute();
    }

    public function post(string $url, array $fields = []) {
        $options = [URLOPT_URL => $url,
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS => http_build_query($fields)];

        $this->setOptions($options);

        return $this->execute();
    }

    public function put(string $url, array $fields = []) {
        $options = [CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'PUT',
                    CURLOPT_HTTPHEADER => ['Content-Length: ' . strlen(http_build_query($fields))],
                    CURLOPT_POSTFIELDS => http_build_query($fields)];

        $this->setOptions($options);

        return $this->execute();
    }

    public function delete(string $url) {
        $options = [CURLOPT_URL => $url,
                    CURLOPT_POST => 0,
                    CURLOPT_CUSTOMREQUEST => 'DELETE',
                    CURLOPT_HTTPHEADER => ['Content-Length: 0']];

        $this->setOptions($options);

        return $this->execute();
    }

    public function setOptions(array $options = []) {
        curl_reset($this->ch);

        $result = curl_setopt_array($this->ch, $this->defaults + $options);

        if ($result === false) {
            curl_reset($this->ch);
            curl_setopt_array($this->ch, $this->defaults);
            throw new InvalidArgumentException('Invalid arguments detected.');
        }
    }

    private function execute() {
        if (!$result = curl_exec($this->ch)) {
            throw new LogicException(curl_error($this->ch));
        }

        $curlInfo = curl_getinfo($this->ch);

        if ($curlInfo === false) {
            throw new Exception('There was a problem getting cURL session info.');
        }

        $stdClass = new stdClass();
        $stdClass->code = isset($curlInfo['http_code']) ? $curlInfo['http_code'] : 500;
        $stdClass->response = $result;

        return $stdClass;
    }
}