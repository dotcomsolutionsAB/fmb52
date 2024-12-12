<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserTableForSectorSubSectorForeignKeys extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove existing sector and sub_sector columns if they exist
            $table->dropColumn(['sector', 'sub_sector']);

            // Add foreign key columns
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->unsignedBigInteger('sub_sector_id')->nullable();

            // Set up foreign keys
            $table->foreign('sector_id')->references('id')->on('t_sector')->onDelete('set null');
            $table->foreign('sub_sector_id')->references('id')->on('t_sub_sector')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            // Drop foreign keys and columns
            $table->dropForeign(['sector_id']);
            $table->dropForeign(['sub_sector_id']);
            $table->dropColumn(['sector_id', 'sub_sector_id']);

            // Restore previous columns if needed
            $table->string('sector')->nullable();
            $table->string('sub_sector')->nullable();
        });
    }
}
