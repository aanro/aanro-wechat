<?php

namespace Someline\Component\Wechat\Repositories\Eloquent;

use Someline\Repositories\Eloquent\BaseRepository;
use Someline\Repositories\Criteria\RequestCriteria;
use Someline\Repositories\Interfaces\SomelineWechatRepository;
use Someline\Models\Wechat\SomelineWechat;
use Someline\Validators\SomelineWechatValidator;
use Someline\Component\Wechat\Presenters\SomelineWechatPresenter;

/**
 * Class SomelineWechatRepositoryEloquentBase
 * @package namespace Someline\Component\Wechat\Repositories\Eloquent;
 */
class SomelineWechatRepositoryEloquentBase extends BaseRepository implements SomelineWechatRepository
{

    protected $fieldSearchable = [
        'title' => 'like',
    ];

    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return SomelineWechat::class;
    }

    /**
     * Specify Validator class name
     *
     * @return mixed
     */
    public function validator()
    {

        return SomelineWechatValidator::class;
    }


    /**
     * Specify Presenter class name
     *
     * @return mixed
     */
    public function presenter()
    {

        return SomelineWechatPresenter::class;
    }


    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }
}
