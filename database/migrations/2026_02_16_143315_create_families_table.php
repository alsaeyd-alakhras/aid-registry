<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();

            // هوية المستفيد (المفتاح الحقيقي لمنع التكرار)
            $table->string('national_id', 10);
            $table->string('full_name');
            $table->string('phone', 13)->nullable();

            // بيانات عامة للأسرة (عدلها حسب حقولك الفعلية)
            $table->unsignedSmallInteger('family_members_count')->nullable();
            $table->string('address')->nullable();

            // الزوج/الزوجة (للتنبيه/البحث)
            $table->string('spouse_national_id', 10)->nullable();
            $table->string('spouse_full_name')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique('national_id');

            // البحث اليومي
            $table->index('spouse_national_id');
            $table->index(['full_name', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('families');
    }
};
