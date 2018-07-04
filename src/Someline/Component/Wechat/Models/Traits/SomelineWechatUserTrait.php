<?php

namespace Someline\Component\Wechat\Models\Traits;


use Someline\Component\Wechat\SomelineWechatService;

trait SomelineWechatUserTrait
{

    /**
     * @param $openid
     * @return bool
     */
    public function isSameWechatOpenId($openid)
    {
        return $this->getWechatOpenId() == $openid;
    }

    /**
     * @return mixed
     */
    public function getWechatOpenId()
    {
        return $this->wechat_openid;
    }

    /**
     * @return mixed
     */
    public function getWechatImageUrl()
    {
        return $this->wechat_image_url;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getWechatOriginalData()
    {
        try {
            return collect(json_decode($this->wechat_original));
        } catch (\Exception $e) {
            return collect();
        }
    }

    /**
     * @param $openid
     * @return mixed
     */
    public function setWechatOpenId($openid)
    {
        $this->wechat_openid = $openid;
        return $this->save();
    }

    /**
     * @param $data
     * @return mixed
     */
    public function updateWechatInfo($data)
    {
        $name = $data['nickname'] ?? null;
        if ($name && $this->name != $name) {
            $this->name = $name;
        }
        $avatar = $data['headimgurl'] ?? null;
        if ($avatar && $this->wechat_image_url != $avatar) {
            $this->wechat_image_url = $avatar;
        }
        $gender = $data['sex'] ?? null;
        if ($gender !== null && $this->gender != $gender) {
            $this->gender = $gender == '1' ? 'M' : 'F';
        }
        if ($data) {
            $encoded_data = json_encode($data);
            if ($this->wechat_original != $encoded_data) {
                $this->wechat_original = $encoded_data;
            }
        }
        $this->onUpdateWechatInfo($data);
        return $this->save();
    }

    protected function onUpdateWechatInfo($data)
    {
        // ...
    }

    /**
     * @return array|null
     */
    public function fetchWechatUserDetail()
    {
        $result = null;
        try {
            $wechatOpenId = $this->getWechatOpenId();
            if (!empty($wechatOpenId)) {
                $result = SomelineWechatService::doGetUserDetail($wechatOpenId);
            }
        } catch (\Exception $e) {
        }
        return $result;
    }

    /**
     * @param int $cacheMinutes
     * @return bool
     */
    public function isSubscribeWechat($cacheMinutes = 720)
    {
        $cacheKey = 'SomelineWechat.UserDetailForSubscribe.' . $this->getUserId();
        $isSubscribeWechat = false;

        $fromCache = false;
        $result = \Cache::get($cacheKey);
        if ($result) {
            $fromCache = true;
        } else {
            $result = $this->fetchWechatUserDetail();
        }

        if ($result) {
            $isSubscribeWechat = $result->subscribe;
            if ($isSubscribeWechat && !$fromCache) {
                \Cache::put($cacheKey, $result, $cacheMinutes);
            }
        }

        return $isSubscribeWechat;
    }

}