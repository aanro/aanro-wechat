<?php

namespace Someline\Component\Wechat\Http\Controllers;

use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\AbstractMessage;
use EasyWeChat\Support\Collection;
use Illuminate\Http\Request;
use Someline\Component\Wechat\SomelineWechatService;
use Someline\Http\Controllers\BaseController;
use Someline\Http\Controllers\SomelinePaymentController;
use Someline\Models\Payment\SomelinePayment;

class SomelineWechatControllerBase extends BaseController
{

    /**
     * @param $buttons
     * @param array $matchRule
     */
    public function updateMenu($buttons, $matchRule = [])
    {
        // destroy existing
        $this->destroyMenu();

        // add new menu
        $this->addMenu($buttons, $matchRule);
    }

    /**
     * @param $buttons
     * @param array $matchRule
     */
    public function addMenu($buttons, $matchRule = [])
    {
        $app = SomelineWechatService::getWechatApplication();
        $menu = $app->menu;
        $menu->add($buttons, $matchRule);
    }

    /**
     * @param null $menuId
     */
    public function destroyMenu($menuId = null)
    {
        $app = SomelineWechatService::getWechatApplication();
        $menu = $app->menu;
        $menu->destroy($menuId);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function servePayNotify()
    {
        $wechat = SomelineWechatService::getWechatApplication();

        info('servePayNotify: ' . request()->getContent());

        $response = $wechat->payment->handleNotify(function ($notify, $successful) {
            return $this->wechatPayNotifyHandler($notify, $successful);
        });

        return $response;
    }

    /**
     * @param $notify
     * @param $successful
     * @return bool|string
     */
    protected function wechatPayNotifyHandler($notify, $successful)
    {
        info("wechatPayNotifyHandler: $notify , successful: $successful");
        $out_trade_no = $notify->out_trade_no;
//        $somelinePayment = SomelinePayment::findByOutTradeNo($out_trade_no);
//        if (!$somelinePayment) {
//            return 'Order not exist.';
//        }
//        info("found payment" . $somelinePayment->getSomelinePaymentId());
//        if ($somelinePayment->isPaid()) {
//            return true;
//        }
//
//        if ($successful) {
//            $somelinePayment->setPaymentSuccess();
//        }
        return true;
    }

    /**
     * 处理微信的请求消息
     *
     * @return string
     */
    public function serve()
    {
//        info('request arrived.'); # 注意：Log 为 Laravel 组件，所以它记的日志去 Laravel 日志看，而不是 EasyWeChat 日志

        $wechat = SomelineWechatService::getWechatApplication();

        $wechat->server->setMessageHandler(function ($message) {
            return $this->wechatMessageHandler($message);
        });

        $this->serveWechat($wechat);
//        info('return response.');

        return $wechat->server->serve();
    }

    /**
     * @param $wechat
     */
    protected function serveWechat($wechat)
    {
        // ...
    }

    /**
     * @link https://easywechat.org/zh-cn/docs/server.html#基本使用
     * @param $message
     * @return string
     */
    protected function wechatMessageHandler($message)
    {
//        info("wechatMessageHandler: " . $message);

        $type = $message->MsgType;
        $result = $this->onReceivedMessage($type, $message);
        if ($result) {
            return $result;
        } elseif ($result === false) {
            return null;
        }

        switch ($type) {
            case 'event':
                return $this->onReceivedEvent($message);
                break;
            case 'text':
                return $this->onReceivedText($message);
                break;
            case 'image':
                return $this->onReceivedImage($message);
                break;
            case 'voice':
                return $this->onReceivedVoice($message);
                break;
            case 'video':
                return $this->onReceivedVideo($message);
                break;
            case 'location':
                return $this->onReceivedLocation($message);
                break;
            case 'link':
                return $this->onReceivedLink($message);
                break;
            case 'shortvideo':
                return $this->onReceivedShortVideo($message);
                break;
            // ... 其它消息
            default:
                return $this->onReceivedOtherMessage($message);
                break;
        }
    }

    /**
     * # 若直接返回结果，将会直接返回给微信服务器，不再继续处理单独的类型
     *
     * @param $type
     * @param $message
     * @return null
     */
    protected function onReceivedMessage($type, $message)
    {
//        info("onReceivedMessage: " . $message);
//        return "收到消息"; // # 若直接返回结果，不再继续处理单独的类型
    }

    /**
     * $message->MsgType     event
     * $message->Event       事件类型 （如：subscribe(订阅)、unsubscribe(取消订阅) ...， CLICK 等）
     *
     * # 扫描带参数二维码事件
     * $message->EventKey    事件KEY值，比如：qrscene_123123，qrscene_为前缀，后面为二维码的参数值
     * $message->Ticket      二维码的 ticket，可用来换取二维码图片
     *
     * # 上报地理位置事件
     * $message->Latitude    23.137466   地理位置纬度
     * $message->Longitude   113.352425  地理位置经度
     * $message->Precision   119.385040  地理位置精度
     *
     * # 自定义菜单事件
     * $message->EventKey    事件KEY值，与自定义菜单接口中KEY值对应，如：CUSTOM_KEY_001, www.qq.com
     *
     * @param $message
     * @return string
     */
    protected function onReceivedEvent($message)
    {
        $event = $message->Event;
        $qrSceneId = $this->getQrSceneId($message);
        if ($qrSceneId) {
            return $this->onReceivedQRCodeEvent($qrSceneId, $message);
        } else {
            if ($event == 'subscribe') {
                return $this->onReceivedSubscribeEvent($message);
            } else {
                return $this->onReceivedOtherEvent($event, $message);
            }
        }
    }

    /**
     * @param $message
     * @return string
     */
    protected function onReceivedSubscribeEvent($message)
    {
//        return SomelineWechatService::newMessageNews(
//            '欢迎关注 Someline！',
//            'https://www.someline.com/cn/pickhub/images/large/aff8e920599bef515abfedaabf47b60f58e304a2.jpg',
//            'https://www.someline.com/',
//            '等你很久了！'
//        );
        return '欢迎关注 Someline Wechat！';
    }

    /**
     * @param $qrSceneId
     * @param $message
     * @return string
     */
    protected function onReceivedQRCodeEvent($qrSceneId, $message)
    {
        return "Someline Wechat 收到二维码事件消息[$qrSceneId]";
    }

    /**
     * @param $event
     * @param $message
     * @return string
     */
    protected function onReceivedOtherEvent($event, $message)
    {
        return 'Someline Wechat 收到事件消息';
    }

    /**
     * @param $message
     * @return mixed|null
     */
    protected function getQrSceneId($message)
    {
        $event = $message->Event;
        $eventKey = $message->EventKey;
        if ($event == 'subscribe' && starts_with($eventKey, 'qrscene_')) {
            return str_replace('qrscene_', '', $eventKey);
        } else if ($event == 'SCAN') {
            return $eventKey;
        } else {
            return null;
        }
    }

    /**
     * $message->MsgType  text
     * $message->Content  文本消息内容
     *
     * @param $message
     * @return string
     */
    protected function onReceivedText($message)
    {
        return 'Someline Wechat 收到文字消息';
    }

    /**
     * $message->MsgType  image
     * $message->PicUrl   图片链接
     *
     * @param $message
     * @return string
     */
    protected function onReceivedImage($message)
    {
        return 'Someline Wechat 收到图片消息';
    }

    /**
     * $message->MsgType        voice
     * $message->MediaId        语音消息媒体id，可以调用多媒体文件下载接口拉取数据。
     * $message->Format         语音格式，如 amr，speex 等
     * $message->Recognition * 开通语音识别后才有
     *
     * > 请注意，开通语音识别后，用户每次发送语音给公众号时，微信会在推送的语音消息XML数据包中，增加一个 `Recongnition` 字段
     *
     * @param $message
     * @return string
     */
    protected function onReceivedVoice($message)
    {
        return 'Someline Wechat 收到语音消息';
    }

    /**
     * $message->MsgType       video
     * $message->MediaId       视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
     * $message->ThumbMediaId  视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
     *
     * @param $message
     * @return string
     */
    protected function onReceivedVideo($message)
    {
        return 'Someline Wechat 收到视频消息';
    }

    /**
     * $message->MsgType     location
     * $message->Location_X  地理位置纬度
     * $message->Location_Y  地理位置经度
     * $message->Scale       地图缩放大小
     * $message->Label       地理位置信息
     *
     * @param $message
     * @return string
     */
    protected function onReceivedLocation($message)
    {
        return 'Someline Wechat 收到坐标消息';
    }

    /**
     * $message->MsgType      link
     * $message->Title        消息标题
     * $message->Description  消息描述
     * $message->Url          消息链接
     *
     * @param $message
     * @return string
     */
    protected function onReceivedLink($message)
    {
        return 'Someline Wechat 收到链接消息';
    }

    /**
     * $message->MsgType     shortvideo
     * $message->MediaId     视频消息媒体id，可以调用多媒体文件下载接口拉取数据。
     * $message->ThumbMediaId    视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
     *
     * @param $message
     * @return string
     */
    protected function onReceivedShortVideo($message)
    {
        return 'Someline Wechat 收到小视频消息';
    }

    /**
     * @param $message
     * @return string
     */
    protected function onReceivedOtherMessage($message)
    {
        return 'Someline Wechat 收到其它消息';
    }

    /**
     * @param $user_id
     * @return mixed
     */
    protected function getTemporaryQRCodeImage($user_id)
    {
        $QRCode = SomelineWechatService::getWechatApplication()->qrcode;
        $result = $QRCode->temporary($user_id, 6 * 24 * 3600);
        $ticket = $result->ticket;
        $url = $QRCode->url($ticket);
        $image = \Image::make($url);
        return $image->response();
    }

}