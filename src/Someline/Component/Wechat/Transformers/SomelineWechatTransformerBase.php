<?php

namespace Someline\Component\Wechat\Transformers;

use Someline\Models\Wechat\SomelineWechat;
use Someline\Transformers\BaseTransformer;

/**
 * Class SomelineWechatTransformer
 * @package namespace Someline\Component\Wechat\Transformers;
 */
class SomelineWechatTransformerBase extends BaseTransformer
{

    /**
     * Transform the SomelineWechat entity
     * @param SomelineWechat $model
     *
     * @return array
     */
    public function transform(SomelineWechat $model)
    {
        return [
            'someline_wechat_id' => (int)$model->someline_wechat_id,

            /* place your other model properties here */
            'title' => $model->title,
            'body_html' => $model->body_html,
            'body_text' => $model->body_text,

            'created_at' => (string)$model->created_at,
            'updated_at' => (string)$model->updated_at
        ];
    }
}
