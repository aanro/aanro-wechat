<?php

namespace Someline\Component\Wechat\Presenters;

use Someline\Transformers\SomelineWechatTransformer;
use Someline\Presenters\BasePresenter;

/**
 * Class SomelineWechatPresenter
 *
 * @package namespace Someline\Component\Wechat\Presenters;
 */
class SomelineWechatPresenter extends BasePresenter
{
    /**
     * Transformer
     *
     * @return \League\Fractal\TransformerAbstract
     */
    public function getTransformer()
    {
        return new SomelineWechatTransformer();
    }
}
