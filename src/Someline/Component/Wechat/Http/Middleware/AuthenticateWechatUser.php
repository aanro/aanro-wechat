<?php

namespace Someline\Component\Wechat\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Someline\Component\Wechat\SomelineWechatService;
use Someline\Models\Foundation\User;

class AuthenticateWechatUser
{

    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param Authenticate $auth
     */
    public function __construct(Authenticate $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @param array $guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $oauth_user = session('wechat.oauth_user');
        if ($oauth_user && $oauth_user->original) {
            $openid = $oauth_user->id;
            if (!empty($openid)) {

                // check existing
                $user = User::where('wechat_openid', $openid)->first();
                if (!$user) {

                    // get auth user
                    if (auth()->check()) {
                        /** @var User $user */
                        $user = auth()->user();
                        if (!empty($user->getWechatOpenId())) {
                            $user = null;
                        }
                    }

                    // create if not exists
                    if (!$user) {
                        $user = new User([
                            'name' => $oauth_user->name,
                            'status' => '1',
                        ]);
                        $user->setWechatOpenId($openid);
                    }

                }

                // update wechat info
                $user->updateWechatInfo($oauth_user->original);

                // login if needed
                $shouldLogin = true;
                if (auth()->check() && $user->getAuthUserId() == $user->getUserId()) {
                    $shouldLogin = false;
                }
                if ($shouldLogin) {
                    auth()->login($user);
                }

            } else {
                \Log::error('[wechat auth]: user id is empty. ' . json_encode($oauth_user));
            }
        }

        if (!auth()->check()) {
            return $this->auth->handle($request, $next, $guards);
        } else {
            return $next($request);
        }
    }
}
