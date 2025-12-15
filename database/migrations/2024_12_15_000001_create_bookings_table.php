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
        Schema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->date('date');
            $table->string('time', 5); // Format: HH:MM (e.g., "18:00")
            $table->unsignedTinyInteger('guests')->default(1);
            $table->enum('reservation_type', ['dining', 'drinks', 'both']);
            $table->string('phone', 50);
            $table->unsignedTinyInteger('status')->default(0); // 0=pending, 1=accepted, 2=declined
            $table->timestamps();

            // Indexes for filtering
            $table->index('status');
            $table->index('date');
            $table->index('reservation_type');
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

