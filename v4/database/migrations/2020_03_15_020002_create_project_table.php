<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateProjectTable
 */
class CreateProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'project',
            function (Blueprint $table) {
                $table->id();
                $table->string('name', 20)->comment('名稱');
                $table->string('version', 10)->comments('doc version');
                $table->boolean('is_enable')->default(0)->comment('projects 啟用狀態');
                $table->boolean('is_notify')->default(0)->comment('是否需要推播通知');
                $table->string('maintainer', 20)->comment('文件聯繫人');
                $table->text('url')->commect('文件 domain');
                $table->text('doc_url')->comment('document json domain');
                $table->timestamp('created_at')->useCurrent()->comment('建立時間');
                $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('更新時間');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('project');
    }
}
