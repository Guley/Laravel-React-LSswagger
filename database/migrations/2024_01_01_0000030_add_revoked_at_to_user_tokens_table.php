<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_tokens', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('user_tokens', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
