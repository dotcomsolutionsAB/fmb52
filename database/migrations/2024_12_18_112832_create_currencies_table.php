<?php



use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->string('country_name', 100); // Country name
            $table->string('currency_name', 100); // Currency name
            $table->string('currency_code', 10)->unique(); // ISO Currency code
            $table->string('currency_symbol', 10); // Currency symbol
            $table->timestamps(); // Created at and Updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
}