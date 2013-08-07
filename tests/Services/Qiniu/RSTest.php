<?php
require_once str_replace(array('tests', 'Test.php'), array('src', '.php'), __FILE__);
class RSTest extends PHPUnit_Framework_TestCase
{
    private $conf = array(
        'accessKey' => 'foo',
        'secretKey' => 'bar',
    );

    public function testDeleteFile()
    {
        $c = new Services_Qiniu_RS('com-example-img-agc', $this->conf);
        try{
            $r = $c->deleteFile('2.jpg');
            var_dump($r);
            $this->assertEquals(true, $r);
        } catch (PEAR_Exception $e) {
            echo $e->getCode();
            echo $e->getMessage();
            $this->assertEquals(true, false);
        }
    }

    public function testUploadFile()
    {
        $c = new Services_Qiniu_RS('com-example-img-agc', $this->conf);
        try{
            $headers = array(
                'Content-Type' => 'image/jpeg',
            );
            $r = $c->uploadFile('/home/u1/2.jpg', '/2.jpg', $headers);
            //$headers = array(
            //    'Content-Type' => 'application/vnd.android.package-archive',
            //);
            //$r = $c->uploadFile('/home/u1/demo-0.0.1.apk', 'demo-0.0.1.apk', $headers);
            //$r = $c->uploadFile('/home/u1/demo-0.0.1.ipa', 'demo-0.0.1.ipa', $headers);
            //$r = $c->uploadFile('/home/u1/demo.plist', 'demo.plist', $headers);
            var_dump($r);
            $this->assertEquals(true, isset($r['uri']));
        } catch (PEAR_Exception $e) {
            echo $e->getCode();
            echo $e->getMessage();
            $this->assertEquals(true, false);
        }
    }
}
?>
