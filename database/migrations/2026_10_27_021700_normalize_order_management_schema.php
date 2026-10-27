<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'order_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_number')->nullable();
            });
        }

        if (! Schema::hasColumn('orders', 'customer_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable();
            });
        }

        if (! Schema::hasColumn('orders', 'staff_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreignId('staff_id')->nullable();
            });
        }

        if (! Schema::hasColumn('orders', 'total')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('total', 10, 2)->default(0);
            });
        }

        if (! Schema::hasColumn('orders', 'status')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('status')->default('pending');
            });
        }

        if (! $this->hasIndex('orders', ['order_number'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('order_number');
            });
        }

        if (Schema::hasColumn('orders', 'name') && ! DB::table('orders')->exists()) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique('orders_email_unique');
                $table->dropColumn([
                    'name',
                    'email',
                    'email_verified_at',
                    'password',
                    'remember_token',
                ]);
            });
        } elseif (Schema::hasColumn('orders', 'name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('name')->nullable()->change();
                $table->string('email')->nullable()->change();
                $table->timestamp('email_verified_at')->nullable()->change();
                $table->string('password')->nullable()->change();
            });
        }

        DB::table('orders')
            ->whereNull('order_number')
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['order_number' => 'ORD-'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)]);
                }
            });

        if (! $this->hasForeignKey('orders', 'customer_id', 'customers')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            });
        }

        if (! $this->hasForeignKey('orders', 'staff_id', 'users')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('staff_id')->references('id')->on('users')->restrictOnDelete();
            });
        }

        $productForeignKey = collect(Schema::getForeignKeys('order_items'))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['product_id']
                && $foreignKey['foreign_table'] === 'products');

        if ($productForeignKey !== null && ! in_array($productForeignKey['on_delete'], ['restrict', 'no action'], true)) {
            Schema::table('order_items', function (Blueprint $table) use ($productForeignKey) {
                $table->dropForeign($productForeignKey['columns']);
            });

            $productForeignKey = null;
        }

        if ($productForeignKey === null) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['customer_id', 'staff_id'] as $column) {
            if ($this->hasForeignKey('orders', $column, $column === 'customer_id' ? 'customers' : 'users')) {
                Schema::table('orders', function (Blueprint $table) use ($column) {
                    $table->dropForeign([$column]);
                });
            }
        }

        $productForeignKey = collect(Schema::getForeignKeys('order_items'))
            ->first(fn (array $foreignKey): bool => $foreignKey['columns'] === ['product_id']
                && $foreignKey['foreign_table'] === 'products');

        if ($productForeignKey !== null) {
            Schema::table('order_items', function (Blueprint $table) use ($productForeignKey) {
                $table->dropForeign($productForeignKey['columns']);
            });

            Schema::table('order_items', function (Blueprint $table) {
                $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            });
        }

        if (Schema::hasColumn('orders', 'order_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropUnique(['order_number']);
                $table->dropColumn(['order_number', 'customer_id', 'staff_id', 'total', 'status']);
            });
        }

        if (! Schema::hasColumn('orders', 'name')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('name')->nullable();
                $table->string('email')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->rememberToken();
            });
        }
    }

    /**
     * Determine whether a table already has an index for the given columns.
     *
     * @param  list<string>  $columns
     */
    private function hasIndex(string $table, array $columns): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index): bool => $index['columns'] === $columns);
    }

    private function hasForeignKey(string $table, string $column, string $referencedTable): bool
    {
        return collect(Schema::getForeignKeys($table))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                && $foreignKey['foreign_table'] === $referencedTable);
    }
};
