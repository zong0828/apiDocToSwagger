<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CreateDocumentTable
 */
class CreateDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'document',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('project_id')->comments('project id');
                $table->string('version', 10)->comments('doc version');
                $table->longText('content')->comments('doc content');
                $table->timestamp('created_at')->useCurrent()->comment('建立時間');
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
        Schema::dropIfExists('document');
    }
}
