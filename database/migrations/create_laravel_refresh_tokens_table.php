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
    public function down()
    {
        Schema::dropIfExists($this->getTable());
    }

    private function getTable()
    {
        return config('refresh-token.table');
    }
};
