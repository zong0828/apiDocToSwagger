<?php

namespace App\Repositories\Models;

use Illuminate\Database\Eloquent\Model;
use App\Repositories\Models\Project;
use App\Casts\Repositories\Models\Casts\Json;

/**
 * Document Model
 */
class Document extends Model
{
    protected $table = 'document';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                           'project_id',
                           'version',
                           'content'
    ];

    protected $casts = [
        'content' => Json::class,
    ];

    public $timestamps = false;

    /**
     * 取得 Porject 對應的文件版號
     *
     * @return void
     */
    public function project()
    {
        return $this->hasMany(Project::class, 'id');
    }
}
