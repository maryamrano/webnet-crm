<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            // Communication/follow-up history attached to any CRM record.
            // Polymorphic columns cannot carry a database foreign key by design;
            // visibility is governed by the parent record (Phase 5 policies).
            $table->morphs('activityable');
            // Author: if the author account is removed, the history entry stays.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 16)->index(); // call, email, meeting, note
            $table->string('subject')->nullable();
            $table->text('body');
            $table->dateTime('happened_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
