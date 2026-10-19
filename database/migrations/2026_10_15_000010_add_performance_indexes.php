<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 7 performance audit: indexes for the columns the visibility scopes,
 * filters and reports actually query (ownership/assignment lookups and the
 * task overdue/reminder scans). Added after measuring query patterns; all are
 * plain b-tree indexes, portable across SQLite/MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->index('assigned_to');
            $table->index('created_by');
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->index('owner_id');
            $table->index('created_by');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('assigned_to');
            $table->index(['status', 'due_at']);
            $table->index('reminder_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('deals', function (Blueprint $table) {
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['created_by']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['status', 'due_at']);
            $table->dropIndex(['reminder_at']);
        });
    }
};
