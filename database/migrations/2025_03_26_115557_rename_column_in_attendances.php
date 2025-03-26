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
        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('latitude', 'start_latitude');
            $table->renameColumn('longitude', 'start_longitude');


            $table->double("schedule_latitude")->nullable()->change();
            $table->double("schedule_longitude")->nullable()->change();
            $table->time("schedule_start_time")->nullable()->change();
            $table->time("schedule_end_time")->nullable()->change();
            $table->time('start_time')->nullable()->change();
            $table->time('end_time')->nullable()->change();


            $table->double('end_latitude')->after('start_longitude')->nullable();
            $table->double('end_longitude')->after('end_latitude')->nullable();
            // status kehadiran
            $table->enum('status', ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alfa'])->default('Hadir')->after('end_longitude');
            $table->longText('keterangan')->nullable()->after('status');
            $table->string('file_keterangan')->nullable()->after('keterangan');

            //keterlambatan
            $table->string('keterlambatan')->nullable()->after('file_keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->renameColumn('start_latitude', 'latitude');
            $table->renameColumn('start_longitude', 'longitude');

            $table->dropColumn('end_latitude');
            $table->dropColumn('end_longitude');
            $table->dropColumn('status');
            $table->dropColumn('keterangan');
            $table->dropColumn('file_keterangan');
            $table->dropColumn('keterlambatan');

            $table->double("schedule_latitude")->change();
            $table->double("schedule_longitude")->change();
            $table->time("schedule_start_time")->change();
            $table->time("schedule_end_time")->change();
            $table->time('start_time')->change();
            $table->time('end_time')->change();
        });
    }
};
