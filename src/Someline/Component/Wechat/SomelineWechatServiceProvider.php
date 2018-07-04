<?php

namespace Someline\Component\Wechat;


use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Route;
use Someline\Models\Wechat\SomelineWechat;
use Someline\Repositories\Eloquent\SomelineWechatRepositoryEloquent;
use Someline\Repositories\Interfaces\SomelineWechatRepository;

class SomelineWechatServiceProvider extends ServiceProvider
{

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        if (class_exists(SomelineWechat::class)) {
            Relation::morphMap([
                SomelineWechat::MORPH_NAME => SomelineWechat::class,
            ]);
        }
        $this->loadMigrationsFrom(__DIR__ . '/../../../migrations');
        $this->publishes([
            __DIR__ . '/../../../config/config.php' => config_path('someline-wechat.php'),

            // master files
            __DIR__ . '/../../../master/Api/SomelineWechat.php.dist' => app_path('Models/Wechat/SomelineWechat.php'),
            __DIR__ . '/../../../master/Api/SomelineWechatRepository.php.dist' => app_path('Repositories/Interfaces/SomelineWechatRepository.php'),
            __DIR__ . '/../../../master/Api/SomelineWechatRepositoryEloquent.php.dist' => app_path('Repositories/Eloquent/SomelineWechatRepositoryEloquent.php'),
            __DIR__ . '/../../../master/Api/SomelineWechatsController.php.dist' => app_path('Api/Controllers/SomelineWechatsController.php'),
            __DIR__ . '/../../../master/Api/SomelineWechatTransformer.php.dist' => app_path('Transformers/SomelineWechatTransformer.php'),
            __DIR__ . '/../../../master/Api/SomelineWechatValidator.php.dist' => app_path('Validators/SomelineWechatValidator.php'),
            __DIR__ . '/../../../master/Http/Console/SomelineWechatController.php.dist' => app_path('Http/Controllers/Console/SomelineWechatController.php'),
            __DIR__ . '/../../../master/Http/SomelineWechatController.php.dist' => app_path('Http/Controllers/SomelineWechatController.php'),

            // resources folders
            __DIR__ . '/../../../resources/assets/js/console' => resource_path('assets/js/components/console/wechats'),
            __DIR__ . '/../../../resources/views/console' => resource_path('views/console/wechats'),
            __DIR__ . '/../../../resources/views/app/layout' => resource_path('views/app/layout'),
            __DIR__ . '/../../../resources/views/someline' => resource_path('views/vendor/someline'),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/config.php',
            'someline-wechat'
        );

        // repository
        if (interface_exists(SomelineWechatRepository::class)) {
            $this->app->bind(SomelineWechatRepository::class, SomelineWechatRepositoryEloquent::class);
        }
    }

    public static function core_routes()
    {
        Route::group(['prefix' => 'wechat'], function () {

            Route::any('/', 'SomelineWechatController@serve');
            Route::any('/pay/notify', 'SomelineWechatController@servePayNotify');

            Route::group(['middleware' => ['wechat.oauth', 'auth.wechat']], function () {
                Route::any('/pay/{id}', 'SomelinePaymentController@payUsingWechat');
                Route::any('/pay/dev/{id}', 'SomelinePaymentController@payUsingWechat');
            });

        });
    }

    /**
     * @param $name
     * @param null $default
     * @return mixed
     */
    public static function getConfig($name, $default = null)
    {
        return config('someline-wechat.' . $name, $default);
    }

}