<?php
namespace Database\Factories;

use App\Models\{Message, User, Chantier};
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition()
    {
        return [
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'chantier_id' => $this->faker->boolean(70) ? Chantier::factory() : null,
            'subject' => $this->faker->sentence(6),
            'body' => $this->faker->paragraphs(3, true),
            'is_read' => $this->faker->boolean(60),
            'read_at' => $this->faker->boolean(60) ? $this->faker->dateTimeBetween('-1 week', 'now') : null,
        ];
    }

    public function unread()
    {
        return $this->state([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function read()
    {
        return $this->state([
            'is_read' => true,
            'read_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function withChantier()
    {
        return $this->state([
            'chantier_id' => Chantier::factory(),
            'subject' => 'À propos du chantier : ' . $this->faker->words(3, true),
        ]);
    }

    public function systemMessage()
    {
        return $this->state([
            'chantier_id' => null,
            'subject' => $this->faker->randomElement([
                'Bienvenue sur la plateforme',
                'Mise à jour importante',
                'Maintenance programmée',
                'Nouvelles fonctionnalités',
            ]),
        ]);
    }
}
