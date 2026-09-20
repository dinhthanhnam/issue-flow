<?php

namespace App\Events;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssueUpdated
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Issue $issue,
        public User $user,
        public array $changes,
    ) {}

    //    /**
    //     * Get the channels the event should broadcast on.
    //     *
    //     * @return array<int, Channel>
    //     */
    //    public function broadcastOn(): array
    //    {
    //        return [
    //            new PrivateChannel('channel-name'),
    //        ];
    //    }
}
