<?php

use App\Enums\AttendanceScheduleType;
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
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->string(
                'schedule_type',
                20
            )
                ->default(
                    AttendanceScheduleType::Regular->value
                )
                ->after('name');

            $table->index(
                [
                    'schedule_type',
                    'is_active',
                ],
                'attendance_schedules_type_active_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_schedules', function (Blueprint $table) {
            $table->dropIndex(
                'attendance_schedules_type_active_index'
            );

            $table->dropColumn(
                'schedule_type'
            );
        });
    }
};
