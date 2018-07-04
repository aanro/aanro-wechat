<?php

namespace Someline\Component\Wechat\Http\Middleware;

use Closure;
use EasyWeChat\Foundation\Application;
use Event;
use Illuminate\Auth\Middleware\Authenticate;
use Log;
use Overtrue\LaravelWechat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWechat\Middleware\OAuthAuthenticate;
use Someline\Component\Wechat\SomelineWechatService;
use Someline\Models\Foundation\User;

class WechatOAuth extends OAuthAuthenticate
{

    /**
     * Use Service Container would be much artisan.
     */
    protected $wechat;

    /**
     * Inject the wechat service.
     * @param Application $wechat
     */
    public function __construct(Application $wechat)
    {
        $this->wechat = $wechat;
    }

    public function handle($request, Closure $next, $scopes = null)
    {
        $isNewSession = false;
        $onlyRedirectInWeChatBrowser = config('wechat.oauth.only_wechat_browser', false);

        if ($onlyRedirectInWeChatBrowser && !$this->isWeChatBrowser($request)) {
            if (config('debug')) {
                Log::debug('[not wechat browser] skip wechat oauth redirect.');
            }

            return $next($request);
        }

        $scopes = $scopes ?: config('wechat.oauth.scopes', ['snsapi_base']);

        if (is_string($scopes)) {
            $scopes = array_map('trim', explode(',', $scopes));
        }

        if (!session('wechat.oauth_user') || $this->needReauth($scopes)) {
            if ($request->has('code')) {
                $user = $this->wechat->oauth->user();
                $id = $user->id;
                if (!empty($id)) {
                    session(['wechat.oauth_user' => $user]);
                    $isNewSession = true;

                    Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'), $isNewSession));

                    return redirect()->to($this->getTargetUrl($request));
                } else {
                    Log::error('[wechat oauth user get failed] ' . json_encode($user));
                }
            }

            session()->forget('wechat.oauth_user');

            return $this->wechat->oauth->scopes($scopes)->redirect($request->fullUrl());
        }

        Event::fire(new WeChatUserAuthorized(session('wechat.oauth_user'), $isNewSession));

        return $next($request);
    }

}
