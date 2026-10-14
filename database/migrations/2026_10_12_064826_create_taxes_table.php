<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Contoh: PPN11, PPH23, PPH21');
            $table->string('name')->comment('Contoh: PPN 11%, PPh 23 Jasa');
            $table->enum('type', ['PPN', 'PPh21', 'PPh23', 'PPh_Final']);
            $table->decimal('rate', 5, 2)->comment('Persentase Pajak, misal: 11.00');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->comment('COA penampung pajak');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
