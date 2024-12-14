<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('t_sub_sector', function (Blueprint $table) {
            // Add the new sector_id column first (nullable temporarily)
            $table->unsignedBigInteger('sector_id')->nullable()->after('jamiat_id');
        });
    
        // Migrate the existing data
        DB::statement('
            UPDATE t_sub_sector s
            JOIN t_sector t ON s.sector = t.name
            SET s.sector_id = t.id
        ');
    
        Schema::table('t_sub_sector', function (Blueprint $table) {
            // Drop the old sector column
            $table->dropColumn('sector');
    
            // Make sector_id non-nullable and add the foreign key
            $table->foreign('sector_id')
                ->references('id')
                ->on('t_sector')
                ->onDelete('cascade');
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('t_sub_sector', function (Blueprint $table) {
            //
        });
    }
};
