<?php

namespace Someline\Component\Wechat;


use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Overtrue\LaravelWechat\ServiceProvider;
use Overtrue\Socialite\User as SocialiteUser;

class LaravelWechatServiceProvider extends ServiceProvider
{

    /**
     * Setup the config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/../../../config/wechat.php');

        if ($this->app instanceof LaravelApplication) {
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    $source => config_path('wechat.php'),
                ]);
            }

            // 创建模拟授权
            $this->setUpMockAuthUser();
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('wechat');
        }

        $this->mergeConfigFrom($source, 'wechat');
    }


    /**
     * 创建模拟登录.
     */
    protected function setUpMockAuthUser()
    {
        $user = config('wechat.mock_user');

        if (is_array($user) && !empty($user['openid']) && config('wechat.enable_mock')) {
            $user = new SocialiteUser([
                'id' => array_get($user, 'openid'),
                'name' => array_get($user, 'nickname'),
                'nickname' => array_get($user, 'nickname'),
                'avatar' => array_get($user, 'headimgurl'),
                'email' => null,
                'original' => array_merge($user, ['privilege' => []]),
            ]);

            // Mock token to prevent fatal error when using clockwork
            $user->setToken(new \Overtrue\Socialite\AccessToken([
                'access_token' => 'mock_access_token',
            ]));

            session(['wechat.oauth_user' => $user]);
        }
    }

}