<?php

namespace app\common\service;

/**
 * 网络服务层
 * Class HttpClientService
 * @package app\common\service
 */
class HttpClientService extends BaseService
{
    /**
     * 发送请求
     * @param string $method 请求方法 UPLOAD为文件,上传文件路径键名path
     * @param string $url 请求url
     * @param array $data 数据
     * @param array $headers 请求头
     * @param array $option 请求选项
     * @return array
     */
    public static function request($method, $url, $data, $headers = [], $option = [])
    {
        $default_headers = [
            'Content-Type: application/json',
            'Connection: Keep-Alive'
        ];

        $method = strtoupper($method);
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch,CURLOPT_HEADER,true);
        curl_setopt($ch,CURLOPT_HTTPHEADER,(empty($headers) ? $default_headers : $headers));
        curl_setopt($ch,CURLOPT_USERAGENT,'JMessage-Api-PHP-Client');
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,20);
        curl_setopt($ch,CURLOPT_TIMEOUT,120);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_CUSTOMREQUEST,('UPLOAD' == $method) ? 'POST' : $method);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);

        if (!empty($data)) {
            if ('UPLOAD' == $method) {
                if (class_exists('\CURLFile')) {
                    curl_setopt($ch,CURLOPT_SAFE_UPLOAD,true);
                    curl_setopt($ch,CURLOPT_POSTFIELDS,['filename' => new \CURLFile($data['path'])]);
                } else {
                    if (defined('CURLOPT_SAFE_UPLOAD')) {
                        curl_setopt($ch,CURLOPT_SAFE_UPLOAD,false);
                    }
                    curl_setopt($ch,CURLOPT_POSTFIELDS,['filename' => '@' . $data['path']]);
                }
            } else {
                curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($data));
            }
        }

        if(!empty($option)) {
            foreach ($option as $k => $v) {
                curl_setopt($ch,$k,$v);
            }
        }

        $output = curl_exec($ch);

        if($output === false) {
            return queryStatus(0,curl_error($ch));
        } else {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header_text = substr($output, 0, $header_size);
            $body = substr($output, $header_size);
            $headers = array();
            foreach (explode("\r\n", $header_text) as $i => $line) {
                if (!empty($line)) {
                    if ($i === 0) {
                        $headers[0] = $line;
                    } else if (strpos($line, ": ")) {
                        list ($key, $value) = explode(': ', $line);
                        $headers[$key] = $value;
                    }
                }
            }
            $response['headers'] = $headers;
            $response['body'] = json_decode($body, true);
            $response['http_code'] = $httpCode;
        }
        curl_close($ch);
        return $response;
    }
}
