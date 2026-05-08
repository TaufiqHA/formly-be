<?php

namespace Database\Factories;

use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Submission>
 */
class SubmissionFactory extends Factory
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
            'submission_number' => 'SUB-'.date('Y').'-'.strtoupper(Str::random(6)),
            'customer_name' => $this->faker->name(),
            'customer_phone' => $this->faker->phoneNumber(),
            'customer_email' => $this->faker->safeEmail(),
            'customer_company' => $this->faker->company(),
            'status' => $this->faker->randomElement(['new', 'read', 'follow_up', 'done', 'archived', 'spam']),
            'ip_address' => $this->faker->ipv4(),
            'submitted_at' => now(),
        ];
    }
}
