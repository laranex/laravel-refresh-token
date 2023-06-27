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
        Schema::create($this->getTable(), function (Blueprint $table) {
            $table->string('id');
            $table->string('refreshable_id');
            $table->string('refreshable_type');
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->getTable());
    }

    private function getTable(): string
    {
        return config('refresh-token.table');
    }
};
