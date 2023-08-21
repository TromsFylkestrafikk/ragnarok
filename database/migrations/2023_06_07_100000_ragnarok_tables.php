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
            $table->char('sink_id', 255)->comment('Sink this import belongs to');
            $table->timestamp('started_at')->nullable()->comment('When import from sink started');
            $table->timestamp('finished_at')->nullable()->comment('When import from sink finished');
            $table->enum('status', [
                'new',
                'importing',
                'failed',
                'finished',
            ])->default('new')->comment('Import status');
        });

        Schema::create('ragnarok_chunks', function (Blueprint $table) {
            $table->id()->comment('Chunk ID');
            $table->char('chunk_id', 64)->comment('Chunk id as given by source');
            $table->char('sink_id', 255);
            $table->bigInteger('records')->default(0)->comment('Number of records imported');
            $table->bigInteger('import_id')->nullable()->comment('Import this chunk belongs to');
            $table->enum('fetch_status', [
                'new',
                'in_progress',
                'finished',
                'failed',
            ])->default('new')->comment('Raw data retrieval status');
            $table->timestamp('fetched_at')->nullable()->comment('Fetch timestamp');
            $table->enum('import_status', [
                'new',
                'in_progress',
                'finished',
                'failed',
            ])->default('new')->comment('Import status');
            $table->timestamp('imported_at')->nullable()->comment('Import timestamp');
            $table->timestamps();
            $table->unique(['chunk_id', 'sink_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ragnarok_sinks');
        Schema::dropIfExists('ragnarok_imports');
        Schema::dropIfExists('ragnarok_chunks');
    }
};
