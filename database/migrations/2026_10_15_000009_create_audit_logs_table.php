<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // Actor: nullable + nullOnDelete so history survives account removal.
            // actor_name is a snapshot taken at event time for exactly that reason.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('action', 64)->index(); // e.g. lead.created, deal.stage_changed
            // No foreign key on the audited entity: the audit row must survive the
            // deletion of the record it describes.
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id')->nullable();
            // JSON: field-level before/after values (business fields only — never
            // passwords, hashes, tokens or other secrets) and safe metadata.
            $table->json('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
