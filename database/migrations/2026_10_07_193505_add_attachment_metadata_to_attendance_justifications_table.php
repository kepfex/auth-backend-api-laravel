<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_justifications', function (Blueprint $table) {
            $table->string(
                'attachment_original_name',
                255
            )
                ->nullable()
                ->after('attachment_path');

            $table->string(
                'attachment_mime_type',
                100
            )
                ->nullable()
                ->after('attachment_original_name');

            $table->unsignedBigInteger(
                'attachment_size'
            )
                ->nullable()
                ->after('attachment_mime_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_justifications', function (Blueprint $table) {
            //
        });
    }
};
