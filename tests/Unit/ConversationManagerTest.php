<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\InstagramPost;
use App\Models\InstagramUser;
use App\Services\ConversationManager;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class ConversationManagerTest extends TestCase
{
    use DatabaseMigrations;

    private ConversationManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ConversationManager();
    }

    public function test_it_creates_new_conversation(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create([
            'instagram_post_id' => 'post_123',
        ]);

        $conv = $this->manager->findOrCreate($user, $post);

        $this->assertInstanceOf(Conversation::class, $conv);
        $this->assertEquals('active', $conv->status);
        $this->assertEquals($user->id, $conv->instagram_user_id);
        $this->assertEquals($post->id, $conv->instagram_post_id);
    }

    public function test_it_reuses_active_conversation(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create(['instagram_post_id' => 'post_123']);

        $conv1 = $this->manager->findOrCreate($user, $post);
        $conv2 = $this->manager->findOrCreate($user, $post);

        $this->assertEquals($conv1->id, $conv2->id);
    }

    public function test_it_creates_new_after_window(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create(['instagram_post_id' => 'post_123']);

        $conv1 = $this->manager->findOrCreate($user, $post);
        $conv1->update(['last_message_at' => now()->subHours(2)]);

        $conv2 = $this->manager->findOrCreate($user, $post);

        $this->assertNotEquals($conv1->id, $conv2->id);
    }

    public function test_it_adds_message(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create(['instagram_post_id' => 'post_123']);

        $conv = $this->manager->findOrCreate($user, $post);
        $this->manager->addMessage($conv, 'user', 'سلام');

        $conv->refresh();
        $this->assertEquals(1, $conv->message_count);
        $this->assertNotNull($conv->last_message_at);
    }

    public function test_it_closes_after_max_messages(): void
    {
        config(['agent.conversation.max_messages' => 3]);

        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create(['instagram_post_id' => 'post_123']);

        $conv = $this->manager->findOrCreate($user, $post);

        for ($i = 0; $i < 3; $i++) {
            $this->manager->addMessage($conv, 'user', "پیام $i");
        }

        $conv->refresh();
        $this->assertEquals('closed', $conv->status);
        $this->assertNotNull($conv->closed_at);
    }

    public function test_it_closes_manually(): void
    {
        $user = InstagramUser::create([
            'instagram_id' => '1',
            'username'     => 'ali',
        ]);
        $post = InstagramPost::create(['instagram_post_id' => 'post_123']);

        $conv = $this->manager->findOrCreate($user, $post);
        $this->manager->close($conv);

        $conv->refresh();
        $this->assertEquals('closed', $conv->status);
    }
}