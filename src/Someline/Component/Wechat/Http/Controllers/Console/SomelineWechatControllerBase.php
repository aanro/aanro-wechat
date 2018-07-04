<?php

namespace Someline\Component\Wechat\Http\Controllers\Console;

use Someline\Http\Controllers\BaseController;

class SomelineWechatControllerBase extends BaseController
{

    public function getWechatList()
    {
        return view('console.wechats.list');
    }

    public function getWechatNew()
    {
        return view('console.wechats.new');
    }

}