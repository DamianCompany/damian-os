<?php

namespace Database\Factories;

use App\Models\DamiOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Printer;
/**
 * @extends Factory<DamiOrder>
 */
class DamiOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+10 days');
        $endDate = fake()->dateTimeBetween($startDate, '+20 days');

        return [
            'quantity' => fake()->numberBetween(1, 20),
            'client_name' => fake()->name(),
            'client_document' => null,
            'requires_invoice' => false,
            'description' => fake()->sentence(),

            'start_date' => $startDate,
            'end_date' => $endDate,

            'filament_grams' => fake()->randomFloat(2, 10, 2000),
            'filament_type' => fake()->randomElement([
                'PLA',
                'PETG',
                'ABS',
            ]),

            'print_hours' => fake()->randomFloat(2, 1, 100),
            'postprocess_hours' => null,

            'electricity_cost' => fake()->randomFloat(2, 0, 100),
            'labor_cost' => fake()->randomFloat(2, 0, 500),
            'unit_sale_price' => fake()->randomFloat(2, 10, 1000),
            'advance' => 0,

            'responsible_name' => fake()->name(),
            'printer_id' => Printer::factory(),
            'printer_location' => 'Taller DAMI 3D',

            'status' => 'pending',
            'created_by' => null,
        ];
    }
    public function requiringInvoice(): static
{
    return $this->state(fn (array $attributes): array => [
        'requires_invoice' => true,
        'client_document' => fake()->numerify('20#########'),
    ]);
}
}
