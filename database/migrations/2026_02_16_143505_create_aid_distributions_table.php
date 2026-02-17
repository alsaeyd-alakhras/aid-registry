<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('aid_distributions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('family_id')
                ->constrained('families')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('office_id')
                ->constrained('offices')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // cash | in_kind
            $table->enum('aid_mode', ['cash', 'in_kind']);

            // للعيني فقط
            $table->foreignId('aid_item_id')
                ->nullable()
                ->constrained('aid_items')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // للنقدي فقط
            $table->decimal('cash_amount', 12, 2)->nullable();

            $table->timestamp('distributed_at')->useCurrent();

            // من أنشأ العملية
            $table->foreignId('created_by')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // بدل الحذف: حالة العملية
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes للتقارير والفلترة
            $table->index(['office_id', 'distributed_at']);
            $table->index(['family_id', 'distributed_at']);
            $table->index(['aid_item_id', 'distributed_at']);
            $table->index(['status', 'distributed_at']);
            $table->index('created_by');
            $table->index('distributed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aid_distributions');
    }
};
