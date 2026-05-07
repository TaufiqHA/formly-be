<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'label' => $this->faker->word(),
            'field_type' => $this->faker->randomElement(['text', 'textarea', 'radio', 'checkbox', 'select']),
            'placeholder' => $this->faker->sentence(),
            'is_required' => $this->faker->boolean(),
            'options' => null,
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
