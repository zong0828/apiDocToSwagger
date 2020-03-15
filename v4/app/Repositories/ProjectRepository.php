<?php

namespace App\Repositories;

use App\Repositories\Models\Project;

/**
 * ProjectRepo
 */
class ProjectRepository
{
    private $model;

    /**
     * Project
     *
     * @param Project $model Project model
     *
     * @return void
     */
    public function __construct(Project $model)
    {
        $this->model = $model;
    }

    /**
     * 取得 Project 列表
     *
     * @param array   $filter      filter
     * @param boolean $getDocument 是否取得文件資料
     *
     * @return mixed
     */
    public function getListByFilter(array $filter, bool $getDocument = false)
    {
        $query = $this->model->query();

        $query->when(
            isset($filter['is_enable']),
            function ($query) use ($filter) {
                $query->where('is_enable', $filter['is_enable']);
            }
        );

        ($getDocument) && $query->with('document');

        return $query->get();
    }
}
