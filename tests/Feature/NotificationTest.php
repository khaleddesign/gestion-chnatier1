<?php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\{User, Chantier, Notification};
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_their_notifications()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->get(route('notifications.index'));

        $response->assertStatus(200)
                ->assertSee($notification->titre);
    }

    public function test_user_cannot_view_other_users_notifications()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1)
                        ->get(route('notifications.view', $notification));

        $response->assertStatus(403);
    }

    public function test_notification_marked_as_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->post(route('notifications.read', $notification));

        $response->assertStatus(200);
        $this->assertTrue($notification->fresh()->lu);
    }

    public function test_notification_service_prevents_spam()
    {
        $user = User::factory()->create();
        $chantier = Chantier::factory()->create(['client_id' => $user->id]);

        // Créer la première notification
        $notification1 = NotificationService::create(
            $user->id,
            $chantier->id,
            'nouveau_document',
            'Test',
            'Message test'
        );

        // Tenter de créer une notification similaire immédiatement
        $notification2 = NotificationService::create(
            $user->id,
            $chantier->id,
            'nouveau_document',
            'Test 2',
            'Message test 2'
        );

        $this->assertNotNull($notification1);
        $this->assertNull($notification2); // Doit être bloquée
    }

    public function test_api_unread_count()
    {
        $user = User::factory()->create();
        Notification::factory()->count(3)->unread()->create(['user_id' => $user->id]);
        Notification::factory()->count(2)->read()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
                        ->getJson('/api/notifications/unread-count');

        $response->assertStatus(200)
                ->assertJson(['count' => 3]);
    }
}
            