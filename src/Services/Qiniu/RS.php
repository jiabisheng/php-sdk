<?php
/**
 * 七牛云存储 Qiniu Resource (Cloud) Storage
 *
 * @category Services
 * @package  Services_Qiniu
 * @author   sink <sinkcup@163.com>
 * @license  www.opensource.org/licenses/MIT
 * @link     https://sinkcup.github.io/pear
 * @link     https://github.com/qiniu/php-sdk
 */

require_once 'HTTP/Request2.php';
require_once dirname(__FILE__) . '/RS/Exception.php';

class Services_Qiniu_RS
{
    private $bucket;
    private $conf = array(
        'accessKey' => '',
        'secretKey' => '',
        'host'      => array(
            'up'   => 'up.qiniu.com',
            'rs'   => 'rs.qbox.me',
            'rsf'  => 'rsf.qbox.me',
        ),
        'uriSuffix' => '.qiniudn.com',
        'customDomain' => null,
    );

    public function __construct($bucket=null, array $conf=array())
    {
        $this->bucket = $bucket;
        $this->conf = array_merge($this->conf, $conf);
    }

    /**
     * 删除文件，要auth认证
     * @example curl -i -H 'Authorization: QBox asdf' 'http://rs.qbox.me/delete/asdf'
     * @return boolean
     */
    public function deleteFile($remoteFileName)
    {
        $remoteFileName = str_replace('/', '', $remoteFileName);
        $uri = 'http://' . str_replace('//', '/', $this->conf['host']['rs'] . '/delete/') . $this->encode($this->bucket . ':' . $remoteFileName);
        $policy =  array('scope' => $this->bucket, 'deadline' => time() + 3600);
        $tmp = parse_url($uri);
        $auth = $this->sign($tmp['path'] . "\n");

        $http = new HTTP_Request2($uri, HTTP_Request2::METHOD_POST);
        $http->setHeader(array('Authorization' => 'QBox ' . $auth));
        $r = $http->send();
        $body = json_decode($r->getBody(), true);
        $code = $r->getStatus();
        //612是文件不存在
        if($code == 200 || $code == 612) {
            return true;
        }
        throw new Services_Qiniu_RS_Exception($body['error'], $code);
    }
    
    private function encode($str) {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($str));
    }

    /**
     * 上传文件，要token认证
     * @example curl -i -F 'file=@2.jpg' -F 'token=asdf' -F 'key=2.jpg' 'http://up.qiniu.com/' 
     * @example ./qrsync ./conf.json
     * @return array array(
        'uri' => 'http://com-example-dl.qiniudn.com/2.jpg'
        }
     */
    public function uploadFile($localPath, $remoteFileName, $headers=array())
    {
        $remoteFileName = str_replace('/', '', $remoteFileName);
        $uri = 'http://' . str_replace('//', '/', $this->conf['host']['up'] . '/');
        //scope中指定文件，就可以覆盖。如果只写bucket，则重复上传会出现错误：614 文件已存在。
        $policy =  array('scope' => $this->bucket . ':' . $remoteFileName, 'deadline' => time() + 3600);
        $data = $this->encode(json_encode($policy));
        $token = $this->sign($data) . ':' . $data;

        //$hash = hash_file('crc32b', $localPath);
        //$tmp = unpack('N', pack('H*', $hash));
        $fields = array(
            'token' => $token,
            'key'   => $remoteFileName,
	    //'crc32' => sprintf('%u', $tmp[1]),
        );
        $http = new HTTP_Request2($uri, HTTP_Request2::METHOD_POST);
        $contentType = isset($headers['Content-Type']) ? $headers['Content-Type'] : 'multipart/form-data';
        $http->addUpload('file', $localPath, null, $contentType);
        $http->addPostParameter($fields);
        //$http->setHeader($headers);
        $r = $http->send();
        $body = json_decode($r->getBody(), true);
        $code = $r->getStatus();
        if($code == 200) {
            if(empty($this->conf['customDomain'])) {
                $uri = 'http://' . str_replace('//', '/', $this->bucket . $this->conf['uriSuffix'] . '/' . $body['key']);
            } else {
                $uri = 'http://' . $this->conf['customDomain'] . '/' . $body['key'];
            }
            return array(
                'uri' => $uri,
            );
        }
        throw new Services_Qiniu_RS_Exception($body['error'], $code);
    }

    private function sign($data) {
        return $this->conf['accessKey'] . ':' . $this->encode(hash_hmac('sha1', $data, $this->conf['secretKey'], true));
    }
}
?>
