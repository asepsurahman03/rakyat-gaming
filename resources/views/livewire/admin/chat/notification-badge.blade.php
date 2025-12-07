<?php

use Livewire\Volt\Component;
use App\Models\ChatRoom;

new class extends Component {
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadUnreadCount();
    }

    public function loadUnreadCount(): void
    {
        $this->unreadCount = ChatRoom::where('status', 'active')
            ->whereHas('messages', function ($query) {
                $query->where('is_admin', false)
                    ->where('is_read', false);
            })
            ->count();
    }

    public function getListeners()
    {
        return [
            'echo:admin-chat,NewChatMessage' => 'loadUnreadCount',
            'chatRoomUpdated' => 'loadUnreadCount',
        ];
    }
}; ?>

<div x-data
     x-init="setInterval(() => { @this.call('loadUnreadCount'); }, 3000)">
    @if($unreadCount > 0)
        <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-black bg-red-500 rounded-full">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    @endif
</div>
