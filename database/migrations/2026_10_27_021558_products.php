<?php

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
        if (! Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 10, 2);
                $table->unsignedInteger('stock_quantity');
                $table->boolean('is_available')->default(1);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrent();
            });

            return;
        }

        $hasCategoryForeignKey = collect(Schema::getForeignKeys('products'))
            ->contains(fn (array $foreignKey): bool => $foreignKey['columns'] === ['category_id']
                && $foreignKey['foreign_table'] === 'categories'
                && $foreignKey['foreign_columns'] === ['id']);

        if (! $hasCategoryForeignKey) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
