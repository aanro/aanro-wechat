<?php

namespace Someline\Component\Wechat;


use EasyWeChat\Foundation\Application;
use EasyWeChat\Message\News;
use EasyWeChat\Payment\Order;
use EasyWeChat\Support\Collection;
use function foo\func;
use Someline\Component\Payment\SomelinePaymentService;
use Someline\Models\Foundation\User;
use Someline\Models\Order\SomelineOrder;
use Someline\Models\Payment\SomelinePayment;

class SomelineWechatService
{

    /**
     * @return bool
     */
    public static function isWeChatBrowser()
    {
        return strpos(request()->header('user_agent'), 'MicroMessenger') !== false;
    }

    /**
     * @return Application|wechat
     */
    public static function getWechatApplication()
    {
        /** @var Application $wechat */
        $wechat = app('wechat');
        return $wechat;
    }

    /**
     * @param SomelineOrder $somelineOrder
     * @param SomelinePayment $somelinePayment
     * @param array $data
     * @return Order
     * @throws \Exception
     */
    public static function doCreateOrder(SomelineOrder $somelineOrder, SomelinePayment $somelinePayment, $data = [])
    {
        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $isWechatJs = $somelinePayment->getPaymentMethod() == SomelinePayment::PAYMENT_METHOD_WECHAT_JS;
        if ($isWechatJs) {
            $wechatOpenId = $somelineOrder->getUser()->getWechatOpenId();
            if (empty($wechatOpenId)) {
                throw new \Exception('支付失败： $wechatOpenId 为空。');
            }
        }
        $attributes = [
            'trade_type' => $somelinePayment->getWechatTradeType(), // JSAPI，NATIVE，APP...
            'body' => $somelineOrder->getOrderTitle(),
            'detail' => $somelineOrder->getOrderTitle(),
            'out_trade_no' => $somelinePayment->getOutTradeNo(),
            'total_fee' => $somelinePayment->getAmountInCent(), // 单位：分
            'notify_url' => url($wechatPayment->getMerchant()->notify_url), // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        ];
        if ($isWechatJs) {
            $attributes['openid'] = $wechatOpenId; // trade_type=JSAPI，此参数必传，用户在商户appid下的唯一标识，
        }
        if (!empty($data['notify_url'])) {
            $attributes['notify_url'] = $data['notify_url'];
        }
        info('doCreateOrder: ' . json_encode($attributes));
        return new Order($attributes);
    }

    /**
     * @param SomelineOrder $somelineOrder
     * @param SomelinePayment $somelinePayment
     * @param $refund_amount
     * @return bool
     * @throws \Exception
     */
    public static function doRefundPayment(SomelineOrder $somelineOrder, SomelinePayment $somelinePayment, $refund_amount)
    {
        if (!$somelineOrder->isRefundable()) {
            throw new \Exception('该订单不支持退款。');
        }
        if (!$somelinePayment->isRefundable()) {
            throw new \Exception('该订单的付款信息不允许退款。');
        }

        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $outTradeNo = $somelinePayment->getOutTradeNo();
        $refundNo = $somelinePayment->getRefundNo();

        $wechat_refund_amount_in_cent = $refund_amount * 100;
        $amountInCent = $somelinePayment->getAmount() * 100;
        $result = $wechatPayment->refund($outTradeNo, $refundNo, $amountInCent, $wechat_refund_amount_in_cent);
        info("REFUND: " . json_encode($result));
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            $result = $wechatPayment->queryRefund($outTradeNo);
            info("query REFUND: " . json_encode($result));
            return true;
        } else {
            $message = "退款失败 [{$outTradeNo}]：" . $result;
            \Log::warning($message);
            throw new \Exception($message);
        }
    }

    /**
     * @param Order $order
     * @return mixed
     * @throws \Exception
     */
    public static function doPrepareOrderForPrepayId(Order $order)
    {
        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $result = $wechatPayment->prepare($order);
        if ($result->return_code == 'SUCCESS' && $result->result_code == 'SUCCESS') {
            $prepayId = $result->prepay_id;
            return $prepayId;
        } else {
            $message = "支付失败 [{$order->out_trade_no}]：" . $result;
            \Log::warning($message);
            throw new \Exception($message);
        }
    }

    /**
     * @param $prepayId
     * @param bool $json
     * @return array|string
     * @throws \Exception
     */
    public static function getPaymentConfigJson($prepayId, $json = false)
    {
        if (empty($prepayId)) {
            throw new \Exception("\$prepayId 为空。");
        }
        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $data = $wechatPayment->configForPayment($prepayId, $json);
        return $data;
    }

    /**
     * @param $prepayId
     * @return array|string
     * @throws \Exception
     */
    public static function getPaymentConfigForApp($prepayId)
    {
        if (empty($prepayId)) {
            throw new \Exception("\$prepayId 为空。");
        }
        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $data = $wechatPayment->configForAppPayment($prepayId);
        return $data;
    }

    /**
     * @param SomelineOrder $somelineOrder
     * @param SomelinePayment $somelinePayment
     * @param $out_trade_no
     * @return \EasyWeChat\Support\Collection
     * @throws \Exception
     */
    public static function queryOrder(SomelineOrder $somelineOrder, SomelinePayment $somelinePayment, $out_trade_no)
    {
        if (empty($out_trade_no)) {
            throw new \Exception("\$out_trade_no 为空。");
        }

        if ($somelinePayment->isStatus(SomelinePayment::STATUS_SUCCESS)) {
            return true;
        }

        $wechatPayment = SomelineWechatService::getWechatApplication()->payment;
        $result = $wechatPayment->query($out_trade_no);

        $result_encoded = json_encode($result);
        info("queryOrder: " . $result_encoded);
        if ($result && $result->get('return_code') == 'SUCCESS') {
            $somelinePayment->notify_data = $result_encoded;
            $somelinePayment->notify_result = $result->get('trade_state') ?? $result->get('result_code');
            $somelinePayment->save();
            if ($result->get('result_code') == 'SUCCESS') {
                if ($result->get('trade_state') == 'SUCCESS') {
                    $somelinePayment->setPaymentSuccess($somelineOrder);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param SomelinePayment $somelinePayment
     * @return bool
     * @throws \Exception
     */
    public static function doMerchantPay(SomelinePayment $somelinePayment)
    {
        // check wechat open id
        $wechatOpenId = $somelinePayment->getUser()->getWechatOpenId();
        if (empty($wechatOpenId)) {
            throw new \Exception('支付失败： $wechatOpenId 为空。');
        }

        // assert valid withdraw
        $somelinePayment->assertValidWithdrawPayment();

        $merchantPay = SomelineWechatService::getWechatApplication()->merchant_pay;
        $outTradeNo = $somelinePayment->getOutTradeNo();

        $amountInCent = $somelinePayment->getAmount() * 100;
        $merchantPayData = [
            'partner_trade_no' => $outTradeNo, //随机字符串作为订单号，跟红包和支付一个概念。
            'openid' => $wechatOpenId, //收款人的openid
            'check_name' => 'NO_CHECK',  //文档中有三种校验实名的方法 NO_CHECK OPTION_CHECK FORCE_CHECK
//            're_user_name' => '张三',     //OPTION_CHECK FORCE_CHECK 校验实名的时候必须提交
            'amount' => $amountInCent,  //单位为分
            'desc' => '企业付款',
            'spbill_create_ip' => $somelinePayment->created_ip,  //发起交易的IP地址
        ];
        $result = $merchantPay->send($merchantPayData);

        $result_encoded = json_encode($result);
        if ($result && $result->get('return_code') == 'SUCCESS') {
            $somelinePayment->notify_data = $result_encoded;
            $somelinePayment->notify_result = $result->get('result_code');
            $somelinePayment->save();

            if ($result->get('result_code') == 'SUCCESS') {

                $message = "企业支付成功 [{$outTradeNo}]：" . $result;
                info($message);

                $somelinePayment->setPaymentSuccess();
                return true;
            }
        }
        $somelinePayment->status = SomelinePayment::STATUS_FAILED;
        $somelinePayment->save();
        $message = "企业支付失败 [{$outTradeNo}]：" . $result;
        \Log::warning($message);
        throw new \Exception($message);
    }

    /**
     * @param User $user
     * @param $templateId
     * @param $targetUrl
     * @param $data
     * @return bool
     * @throws \Exception
     */
    public static function doSendTemplateNotice(User $user, $templateId, $targetUrl, $data)
    {
        $notice = SomelineWechatService::getWechatApplication()->notice;

        $wechatOpenId = $user->getWechatOpenId();
        if (empty($wechatOpenId)) {
            throw new \Exception('$wechatOpenId 为空。');
        }
        $result = $notice->uses($templateId)
            ->withUrl($targetUrl)
            ->andData($data)
            ->andReceiver($wechatOpenId)
            ->send();

        if ($result && $result->get('errmsg') == 'ok') {
            return true;
        } else {
            $message = "发送模板消息失败 [{$wechatOpenId}]：" . $result;
            \Log::warning($message);
            return false;
        }
    }

    /**
     * @param User $user
     * @param $amount
     * @param $data
     * @param bool $isGroup
     * @return bool
     * @throws \Exception
     */
    public static function doSendLuckyMoney(User $user, $amount, $data, $isGroup = false)
    {
        $luckyMoney = SomelineWechatService::getWechatApplication()->lucky_money;

        $wechatOpenId = $user->getWechatOpenId();
        if (empty($wechatOpenId)) {
            throw new \Exception('$wechatOpenId 为空。');
        }

        $somelinePayment = SomelinePaymentService::doCreatePaymentForLuckyMoney($user, $amount);
        $outTradeNo = $somelinePayment->getOutTradeNo();

        $amount_in_cent = $amount * 100;
        $luckyMoneyData = array_merge($data, [
            'mch_billno' => $outTradeNo,
            're_openid' => $wechatOpenId,
            'total_num' => !$isGroup ? 1 : ($data['total_num'] ?? 3),   //普通红包固定为1，裂变红包不小于3
            'total_amount' => $amount_in_cent, //单位为分，普通红包不小于100，裂变红包不小于300
//            'send_name' => '测试红包',
//            'wishing' => '祝福语',
//            'act_name' => '测试活动',
//            'remark' => '测试备注',
        ]);

        $type = \EasyWeChat\Payment\LuckyMoney\API::TYPE_NORMAL;
        if ($isGroup) {
            $type = \EasyWeChat\Payment\LuckyMoney\API::TYPE_GROUP;
        }
        $result = $luckyMoney->send($luckyMoneyData, $type);

//        info("send lucky money: " . json_encode($result));
        $somelinePayment->notify_data = json_encode($result);
        $somelinePayment->notify_result = $result->get('result_code') ?? $result->get('return_code');
        $somelinePayment->save();
        if ($result && $result->get('return_code') == 'SUCCESS') {
            if ($result->get('result_code') == 'SUCCESS') {
                $somelinePayment->setPaymentSuccess();
                return true;
            }
        }
        $message = "发送红包失败 [{$wechatOpenId}]：" . $result;
        \Log::warning($message);
        $somelinePayment->updateStatus(SomelinePayment::STATUS_FAILED);
        throw new \Exception(($result['err_code_des'] ?? "未知错误"));
    }

    /**
     * @param string $title
     * @param string|null $image_url
     * @param string|null $open_url
     * @param string|null $description
     * @return News
     */
    public static function newMessageNews(string $title, string $image_url = null, string $open_url = null, string $description = null)
    {
        $news = new News();
        $news->title = $title;
        if ($description) {
            $news->description = $description;
        }
        if ($image_url) {
            $news->image = $image_url;
        }
        if ($open_url) {
            $news->url = $open_url;
        }
        return $news;
    }

    /**
     * @param array $extraJsApiList
     * @return array|string
     */
    public static function generateJsConfig($extraJsApiList = [])
    {
        $js = SomelineWechatService::getWechatApplication()->js;
        $configApiList = SomelineWechatServiceProvider::getConfig('jsApiList', []);
        $debug = SomelineWechatServiceProvider::getConfig('debug', false);
        $config = $js->config(array_merge($configApiList, $extraJsApiList), $debug);
        return $config;
    }

    /**
     * @param null $nextOpenId
     * @return \EasyWeChat\Support\Collection
     */
    public static function doGetUserList($nextOpenId = null)
    {
        $userService = SomelineWechatService::getWechatApplication()->user;
        $results = $userService->lists($nextOpenId);
        return $results;
    }

    /**
     * @param null $nextOpenId
     * @return \EasyWeChat\Support\Collection
     */
    public static function doGetUserListWithDetails($nextOpenId = null)
    {
        $result = SomelineWechatService::doGetUserList($nextOpenId);
        if ($result) {
            $result = $result->toArray();
            if (isset($result['data']['openid'])) {
                $openid_list = $result['data']['openid'];
                $all_results = collect();
                collect($openid_list)->chunk(100)->each(function (\Illuminate\Support\Collection $ids, $key) use (&$all_results) {
                    $results = SomelineWechatService::doBatchGetUserDetail($ids->values()->toArray());
                    if (isset($results['user_info_list'])) {
                        $all_results = $all_results->merge(collect($results['user_info_list']));
                    }
                });
                if (!empty($all_results)) {
                    $result['data']['user_info_list'] = $all_results->toArray();
                }
            }
            $result = new Collection($result);
        }
        return $result;
    }

    /**
     * @param $openId
     * @return array
     */
    public static function doGetUserDetail($openId)
    {
        $userService = SomelineWechatService::getWechatApplication()->user;
        $results = $userService->get($openId);
        return $results;
    }

    /**
     * @param array $openIds
     * @return \EasyWeChat\Support\Collection
     */
    public static function doBatchGetUserDetail(array $openIds)
    {
        $userService = SomelineWechatService::getWechatApplication()->user;
        $results = $userService->batchGet($openIds);
        return $results;
    }

}