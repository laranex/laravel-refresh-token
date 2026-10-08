<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->table(), function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('refreshable_id');
            $table->string('refreshable_type');
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['refreshable_type', 'refreshable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    private function table(): string
    {
        $table = Config::get('refresh-token.table');

        return is_string($table) && $table !== '' ? $table : 'laravel_refresh_tokens';
    }
};
