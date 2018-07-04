<?php

namespace Someline\Component\Wechat\Validators;

use \Prettus\Validator\Contracts\ValidatorInterface;
use \Prettus\Validator\LaravelValidator;

class SomelineWechatValidatorBase extends LaravelValidator
{

    protected $rules = [
        ValidatorInterface::RULE_CREATE => [
            'title' => 'required',
            'body_text' => 'required',
            'body_html' => 'required',
            'pinned' => 'required|boolean',
        ],
        ValidatorInterface::RULE_UPDATE => [
            'title' => 'required',
            'body_html' => 'required',
            'body_text' => 'required',
            'pinned' => 'required|boolean',
        ],
    ];
}
