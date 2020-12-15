<?php

namespace Tiway\ContentSecurity;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;

class AliyunGreen
{

    private $accessKeyId;

    private $accessKeySecret;

    private $regionId;

    private $debug;

    private $timeout;

    private $connectTimeout;

    //检测类型
    const TYPE_TEXT = 'text';
    const TYPE_IMAGE = 'image';
    const TYPE_FILE = 'file';
    const TYPE_VIDEO = 'video';
    const TYPE_VOICE = 'voice';

    //常用默认检测类型
    const TYPE_IMAGE_DEFAULT = array("porn", "terrorism");
    const TYPe_TEXT_DEFAULT = array("antispam");

    /**
     * AliyunGreen constructor.
     * @param null $accessKeyId
     * @param null $accessKeySecret
     * @param string $regionId
     * @param bool $debug
     * @param int $timeout
     * @param int $connectTimeout
     * @throws ClientException
     */
    public function __construct(
        $accessKeyId = null,
        $accessKeySecret = null,
        $regionId = 'cn-shanghai',
        $debug = false,
        $timeout = 6,
        $connectTimeout = 10
    ) {

        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
        $this->regionId = $regionId;
        $this->debug = $debug;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;

        $this->__initialization();
    }

    /**
     * 初始化
     * @throws ClientException
     */
    private function __initialization()
    {
        try {
            AlibabaCloud::accessKeyClient($this->accessKeyId, $this->accessKeySecret)
                ->regionId($this->regionId)// 设置客户端区域，
                ->timeout($this->timeout)  // 超时10秒，使用该客户端且没有单独设置的请求都使用此设置
                ->connectTimeout($this->connectTimeout)// 连接超时10秒
                ->debug($this->debug) // 开启调试
                ->asDefaultClient();
        } catch (\Exception $e) {
            return ['code' => 0, 'msg' => $e->getErrorMessage()];
        }
    }

    /**
     * 提交语音检测任务
     * @param $content
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function voiceAsyncScan(
        $url,
        $scenes = array("antispam"),
        $seed = null,
        $callback = null,
        $live = false,
        $offline = false
    ) {
        $tasks = $this->getTask($url, self::TYPE_VOICE);
        $body = array(
            'tasks' => $tasks,
            'scenes' => $scenes,
            'live' => $live,
            'offline' => $offline,
            'seed' => $seed,
            'callback' => $callback,
        );
        return $this->response('/green/' . self::TYPE_VOICE . '/asyncscan', $body);
    }

    /**
     * @param $url //提交文件检测任务
     * @param null $textScenes //检测内容包含文本时，指定检测场景，取值：antispam。
     * @param null $imageScenes //检测内容包含图片时，指定检测场景，默认：porn：智能鉴黄,terrorism：暴恐涉政识别等等。
     * @param null $callback //异步检测结果回调通知您的URL，支持HTTP/HTTPS。
     * @param null $seed //该值用于回调通知请求中的签名
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function fileAsyncScan($url, $textScenes = null, $imageScenes = null, $callback = null, $seed = null)
    {
        $tasks = $this->getTask($url, self::TYPE_FILE);
        $body = array(
            'tasks' => $tasks,
            'callback' => $callback,
            'seed' => $seed
        );
        if (empty($textScenes)) {
            $body['textScenes'] = array("antispam");
        }
        if (empty($textScenes)) {
            $body['imageScenes'] = array("porn", "terrorism");
        }
        return $this->response('/green/' . self::TYPE_FILE . '/asyncscan', $body);
    }

    /**
     * 图片异步检测
     * @param $url //指定检测对象，JSON数组中的每个元素是一个图片检测任务结构体（image表）。最多支持10个元素，即对10张图片进行检测。
     * @param string[] $scenes //默认：porn：图片智能鉴黄,terrorism：暴恐涉政识别等等。
     * @param null $seed //随机字符串，该值用于回调通知请求中的签名。当使用callback时，该字段必须提供。
     * @param $callback //异步检测结果回调通知您的URL，支持HTTP/HTTPS。该字段为空时，您必须定时检索检测结果。
     * @param array $extras //额外调用参数。
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function imageAsyncScan(
        $url,
        $scenes = array("porn", "terrorism"),
        $seed = null,
        $callback = null,
        $extras = array()
    ) {
        $tasks = $this->getTask($url, self::TYPE_IMAGE);

        $body = array(
            'tasks' => $tasks,
            'scenes' => $scenes,
            'seed' => $seed,
            'callback' => $callback,
        );
        if (!empty($extras)) {
            $body['extras'] = $extras;
        }
        return $this->response('/green/' . self::TYPE_IMAGE . '/asyncscan', $body);
    }

    /**
     * 视频同步检测:视频同步检测接口只支持通过上传视频截帧图片的方式进行检测。如果您想通过上传视频URL的方式进行检测，使用异步检测接口。
     * @param $data
     * @param string[] $scenes //默认：porn：智能鉴黄,terrorism：暴恐涉政识别等等。
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function videoSyncScan($data, $scenes = array("porn", "terrorism"))
    {
        if (!is_array($data)) {
            return ['code' => 0, 'msg' => 'data格式错误'];
        }

        $tasks = [];
        foreach ($data as $k => $v) {
            $tasks[] = [
                'dataId' => uniqid(),
                'frames' => $v,
            ];
        }
        $body = array(
            'tasks' => $tasks,
            'scenes' => $scenes,
        );
        return $this->response('/green/' . self::TYPE_VIDEO . '/syncscan', $body);
    }

    /**
     * 视频异步检测:
     * @param $url
     * @param string[] $scenes //默认：porn：智能鉴黄,terrorism：暴恐涉政识别等等。
     * @param null $seed //随机字符串，该值用于回调通知请求中的签名。当使用callback时，该字段必须提供。
     * @param null $callback //异步检测结果回调通知您的URL，支持HTTP/HTTPS。该字段为空时，您必须定时检索检测结果。
     * @param array $audioScenes //选择一个或多个语音检测场景，在检测视频中图像的同时，对视频中语音进行检测
     * @param bool $live //是否直播。默认为false，表示为普通视频检测；若为直播检测，该值必须传入true。
     * @param bool $offline //是否近线检测模式。默认为false，表示实时检测模式，对于超过了并发路数限制的检测请求会直接拒绝。如果为true，会进入近线检测模式，提交的任务不保证实时处理，但是可以排队处理，在24小时内开始检测。
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function videoAsyncScan(
        $url,
        $scenes = array("porn", "terrorism"),
        $seed = null,
        $callback = null,
        $audioScenes = array(),
        $live = false,
        $offline = false
    ) {
        $tasks = $this->getTask($url, self::TYPE_VIDEO);
        $body = array(
            'tasks' => $tasks,
            'scenes' => $scenes,
            'live' => $live,
            'offline' => $offline,
            'seed' => $seed,
            'audioScenes' => $audioScenes,
            'callback' => $callback,
        );
        return $this->response('/green/' . self::TYPE_VIDEO . '/asyncscan', $body);
    }

    /**
     * 请求api
     * @param $action
     * @param $body
     * @param $params
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    protected function response($action, $body, $params = [])
    {
        try {
            $result = AlibabaCloud::roa()
                ->product('Green')
                ->version('2018-05-09')
                ->pathPattern($action)
                ->method('POST')
                ->options([
                    'query' => $params
                ])
                ->body(json_encode($body))
                ->request();
            if ($result->isSuccess()) {
                return $result->toArray();
            } else {
                return $result;
            }
        } catch (ClientException $e) {
            return ['code' => 0, 'msg' => $e->getErrorMessage()];
        } catch (ServerException $e) {
            return ['code' => 0, 'msg' => $e->getErrorMessage()];
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function generateArray($data)
    {
        $urls = [];
        if (!is_array($data)) {
            $res = json_decode($data, true);
            if (is_null($res)) {
                $urls[] = $data;
            } else {
                $urls = $res;
            }
        } else {
            $urls = $data;
        }
        return $urls;
    }

    /**
     * 元素结构体
     * @param $data
     * @param string $type
     * @return array
     */
    public function getTask($data, $type = self::TYPE_IMAGE)
    {
        $tasks = [];
        $urls = $this->generateArray($data);
        foreach ($urls as $k => $v) {
            $arr = array('dataId' => uniqid());
            if ($type == self::TYPE_TEXT) {
                $arr['content'] = $v;
            } else {
                if (in_array($type, array(self::TYPE_IMAGE, self::TYPE_FILE, self::TYPE_VOICE))) {
                    $arr['url'] = $v;
                } else {
                    if ($type == self::TYPE_VIDEO) {
                        $arr['url'] = $v;
                        $arr['interval'] = 1;
                        $arr['maxFrames'] = 200;
                    }
                }
            }
            $tasks[] = $arr;
        }
        return $tasks;
    }

    /**
     * @param $body
     * @param string $type
     * @return \AlibabaCloud\Client\Result\Result|array
     */
    public function getResults($body, $type = self::TYPE_IMAGE)
    {
        $body = $this->generateArray($body);
        return $this->response('/green/' . $type . '/results', $body);
    }

    /**
     * 停止检测
     * @param $body //JSON数组 要查询的taskId列表。最大长度不超过100。
     * @param $body
     * @param string $type
     * @return \AlibabaCloud\Client\Result\Result|array]
     */
    public function cancelScan($body, $type = self::TYPE_VIDEO)
    {
        $body = $this->generateArray($body);
        return $this->response('/green/' . $type . '/cancelscan', $body);
    }

    /**
     * 图片同步检测
     * @param $url ////指定检测对象，JSON数组中的每个元素是一个图片检测任务结构体（image表）。最多支持10个元素，即对10张图片进行检测。
     * @param string[] $scenes
     * image video 指定检测场景。取值：
     * porn：图片智能鉴黄
     * terrorism：图片暴恐涉政
     * ad：图文违规
     * qrcode：图片二维码
     * live：图片不良场景
     * logo：图片logo
     * text voice，取值：antispam
     * @param array $extras //额外调用参数。
     * @return \AlibabaCloud\Client\Result\Result|array
     *
     * example：
     * scan('https:xxx.jpb',self::TYPE_IMAGE,['porn','terrorism','logo'])
     */
    public function scan($url, $type = self::TYPE_IMAGE, $scenes = self::TYPE_IMAGE_DEFAULT, $extras = array())
    {
        $tasks = $this->getTask($url, $type);
        $body = array(
            'tasks' => $tasks,
            'scenes' => $scenes,
        );
        if (!empty($extras)) {
            $body['extras'] = $extras;
        }
        return $this->response('/green/' . $type . '/scan', $body);
    }

    public function checksum($uid,$seed,$content ){
        $str = $uid .$seed .$content;
        return hash("sha256", $str);
    }
    
}