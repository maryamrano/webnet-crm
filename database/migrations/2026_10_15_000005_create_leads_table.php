<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('email_normalized')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('phone_normalized')->nullable()->index();
            $table->string('company_name')->nullable();
            $table->string('source', 32)->index();
            $table->string('status', 32)->default('new')->index();
            $table->text('notes')->nullable();

            // Assignment/creation: removing a user unassigns, never deletes the lead.
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Conversion outcome. Deliberately NOT unique: many leads may legitimately
            // link to the same company/contact. Repeat-conversion is prevented by the
            // conversion transaction + status check, not by uniqueness here.
            // RESTRICT on delete: a company/contact that a converted lead points to
            // cannot be deleted out from under the conversion history.
            $table->foreignId('converted_company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->foreignId('converted_contact_id')->nullable()->constrained('contacts')->restrictOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
