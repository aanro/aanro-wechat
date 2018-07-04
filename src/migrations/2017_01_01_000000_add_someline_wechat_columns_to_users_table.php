<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomelineWechatColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('wechat_openid')->nullable()->index()->after('user_id');
            $table->string('wechat_image_url')->nullable()->index()->after('email');
            $table->text('wechat_original')->nullable()->after('wechat_image_url');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wechat_openid', 'wechat_image_url', 'wechat_original']);
        });
    }
}
