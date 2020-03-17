<?php

namespace App\Repositories\Models;

use Illuminate\Database\Eloquent\Model;
use App\Repositories\Models\Document;

/**
 * Project Model
 */
class Project extends Model
{
    protected $table = 'project';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
                           'name',
                           'version',
                           'is_enable',
                           'maintainer',
                           'url',
                           'doc_url'
    ];

    public $timestamps = false;

    /**
     * 取得 Porject 對應的文件版號
     *
     * @return void
     */
    public function document()
    {
        return $this->hasMany(Document::class, 'project_id');
    }
}
