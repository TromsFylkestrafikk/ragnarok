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
        $statusValues = ['new', 'in_progress', 'finished', 'failed'];
        Schema::create('ragnarok_sinks', function (Blueprint $table) {
            $table->char('id', 64)->primary()->comment('Unique sink ID');
            $table->char('title', 255)->comment('Title/name of sink for presentation');
            $table->boolean('single_state')->comment('Chunks represent a non-incremental, single state in DB');
            $table->string('impl_class')->comment('Implementation of \Ragnarok\Sink\Sinks\SinkBase');
            $table->enum('status', ['live', 'suspended', 'disabled'])->default('live')->comment('Sink is live, suspended or in completely disabled state');
            $table->timestamps();
        });

        Schema::create('ragnarok_chunks', function (Blueprint $table) use ($statusValues) {
            $table->id()->comment('Chunk ID');
            $table->char('sink_id', 64);
            $table->char('chunk_id', 64)->comment('Chunk id as given by source');
            $table->unsignedBigInteger('sink_file_id')->nullable()->comment('File assocciated with fetched chunk');
            $table->enum('fetch_status', $statusValues)->default('new')->comment('Raw data retrieval status');
            $table->unsignedInteger('fetch_size')->nullable()->comment('Total size of fetched files/data');
            $table->text('fetch_message')->nullable()->comment('Status/error message of last fetch operation');
            $table->char('fetch_version', 128)->nullable()->comment('Version/checksum of downloaded chunk');
            $table->string('fetch_batch')->nullable()->index()->comment('Batch ID of current fetch operation');
            $table->timestamp('fetched_at')->nullable()->comment('Fetch timestamp');
            $table->enum('import_status', $statusValues)->default('new')->comment('Import status');
            $table->unsignedInteger('import_size')->nullable()->comment('Total number of imported records');
            $table->text('import_message')->nullable()->comment('Status/error message of last import operation');
            $table->char('import_version', 128)->nullable()->comment('Import is based on this fetch version/checksum');
            $table->string('import_batch')->nullable()->index()->comment('Batch ID of current import operation');
            $table->timestamp('imported_at')->nullable()->comment('Import timestamp');
            $table->timestamps();
            $table->unique(['chunk_id', 'sink_id']);
        });

        Schema::create('ragnarok_batches', function (Blueprint $table) {
            $table->id()->comment('Required for easier eloquent operations');
            $table->string('batch_id')->unique()->comment('References job_batches.id');
            $table->char('sink_id', 64)->comment('References ragnarok_sinks.id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ragnarok_sinks');
        Schema::dropIfExists('ragnarok_chunks');
        Schema::dropIfExists('ragnarok_batches');
    }
};
