<?php

namespace Database\Factories\Payment;

use App\Models\Payment\CoinTransaction;
use App\Models\User;
use App\Enums\CoinTransactionTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoinTransactionFactory extends Factory
{
    protected $model = CoinTransaction::class;

    public function definition(): array
    {
        return [
            'transactionable_id' => User::factory(),
            'transactionable_type' => User::class,
            'type' => $this->faker->randomElement(['earn', 'spend']),
            'amount' => $this->faker->numberBetween(10, 1000),
            'detail' => $this->faker->randomElement(CoinTransactionTypeEnum::cases())->value,
            'processed_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }

    /**
     * Indicate that the transaction is an earn transaction
     */
    public function earn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earn',
        ]);
    }

    /**
     * Indicate that the transaction is a spend transaction
     */
    public function spend(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'spend',
        ]);
    }

    /**
     * Create transaction with specific detail type
     */
    public function withDetail(CoinTransactionTypeEnum $detail): static
    {
        return $this->state(fn (array $attributes) => [
            'detail' => $detail->value,
        ]);
    }

    /**
     * Create transaction with specific amount
     */
    public function withAmount(int $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Create transaction for specific entity
     */
    public function forEntity($entity): static
    {
        return $this->state(fn (array $attributes) => [
            'transactionable_id' => $entity->id,
            'transactionable_type' => get_class($entity),
        ]);
    }

    /**
     * Create purchased coins transaction
     */
    public function purchased(): static
    {
        return $this->earn()->withDetail(CoinTransactionTypeEnum::PURCHASED);
    }

    /**
     * Create competition gift transaction
     */
    public function competitionGift(): static
    {
        return $this->earn()->withDetail(CoinTransactionTypeEnum::COMPETITION_GIFT);
    }

    /**
     * Create question generation transaction
     */
    public function questionGenerate(): static
    {
        return $this->spend()->withDetail(CoinTransactionTypeEnum::QUESTION_GENERATE);
    }

    /**
     * Create premium question purchase transaction
     */
    public function premiumQuestionPurchase(): static
    {
        return $this->spend()->withDetail(CoinTransactionTypeEnum::PREMIUM_QUESTION_PURCHASE);
    }
} 