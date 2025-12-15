<?php

declare(strict_types=1);

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
        Schema::create('private_reservations', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('email', 255);
            $table->enum('event_type', ['birthday', 'anniversary', 'corporate', 'wedding', 'other']);
            $table->enum('people_range', ['under10', '10to30', '30to50', 'over50']);
            $table->enum('budget', ['under1000', '1000to3000', '3000to5000', '5000to10000', 'over10000']);
            $table->text('message')->nullable();
            $table->unsignedTinyInteger('status')->default(0); // 0=pending, 1=accepted, 2=declined
            $table->timestamps();

            // Indexes for filtering
            $table->index('status');
            $table->index('date');
            $table->index('event_type');
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('private_reservations');
    }
};

