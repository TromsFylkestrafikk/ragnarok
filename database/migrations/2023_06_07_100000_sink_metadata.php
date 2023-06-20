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
        Schema::create('ragnarok_sinks', function (Blueprint $table) {
            $table->id();
            $table->char('name', 255)->comment('Computer readable name of sink');
            $table->timestamps();
        });

        Schema::create('ragnarok_imports', function (Blueprint $table) {
            $table->id()->comment('Sink import ID');
            $table->char('sink_name', 255);
            $table->timestamp('started_at')->nullable()->comment('When import from sink started');
            $table->timestamp('finished_at')->nullable()->comment('When import from sink finished');
            $table->enum('status', [
                'new',
                'importing',
                'failed',
                'finished',
            ])->default('new')->comment('Import status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ragnarok_sinks');
        Schema::dropIfExists('ragnarok_imports');
    }
};
