<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_gallery', function (Blueprint $table) {
            $table->bigIncrements('id_gallery');
            $table->unsignedBigInteger('id_perusahaan');
            $table->unsignedBigInteger('id_project');
            $table->string('label', 100);
            $table->string('file_name', 255);
            $table->string('original_name', 255);
            $table->enum('file_type', ['image', 'video', 'document']);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size');
            $table->string('caption', 500)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->index('id_project');
            $table->index(['id_perusahaan', 'label']);

            $table->foreign('id_project')->references('id_project')->on('project')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_gallery');
    }
};
