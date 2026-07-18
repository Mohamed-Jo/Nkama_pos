<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_cards')) {
            Schema::create('customer_cards', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
                $table->string('card_number')->unique();
                $table->text('barcode')->nullable();
                $table->text('qr_code')->nullable();
                $table->unsignedInteger('points')->default(0);
                $table->decimal('balance', 12, 2)->default(0);
                $table->string('level', 24)->default('Bronze');
                $table->string('status', 24)->default('active');
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }


        // Existing customers receive a card immediately so the module starts complete.
        if (Schema::hasTable('customers')) {
            $now = now();
            $usedCardNumbers = [];
            $generateCardNumber = function () use (&$usedCardNumbers): string {
                do {
                    $cardNumber = 'NK' . (string) random_int(100000000, 999999999);
                } while (isset($usedCardNumbers[$cardNumber]) || DB::table('customer_cards')->where('card_number', $cardNumber)->exists());

                $usedCardNumbers[$cardNumber] = true;

                return $cardNumber;
            };
            DB::table('customers')
                ->orderBy('id')
                ->select('id')
                ->chunkById(200, function ($customers) use ($now, $generateCardNumber) {
                    foreach ($customers as $customer) {
                        $exists = DB::table('customer_cards')->where('customer_id', $customer->id)->exists();

                        if ($exists) {
                            continue;
                        }

                        $cardNumber = $generateCardNumber();

                        DB::table('customer_cards')->insert([
                            'customer_id' => $customer->id,
                            'card_number' => $cardNumber,
                            'barcode' => $cardNumber,
                            'qr_code' => $cardNumber,
                            'points' => 0,
                            'balance' => 0,
                            'level' => 'Bronze',
                            'status' => 'active',
                            'issued_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                });
        }
        if (!Schema::hasTable('point_transactions')) {
            Schema::create('point_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_card_id')->constrained('customer_cards')->cascadeOnDelete();
                $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
                $table->string('type', 24);
                $table->integer('points');
                $table->integer('balance_after')->default(0);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('rewards')) {
            Schema::create('rewards', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedInteger('required_points');
                $table->string('reward_type', 24);
                $table->decimal('reward_value', 12, 2)->default(0);
                $table->string('status', 24)->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('customer_coupons')) {
            Schema::create('customer_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('coupon_code')->unique();
                $table->string('discount_type', 24);
                $table->decimal('discount_value', 12, 2)->default(0);
                $table->timestamp('valid_until')->nullable();
                $table->string('status', 24)->default('active');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_coupons');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('point_transactions');
        Schema::dropIfExists('customer_cards');
    }
};
