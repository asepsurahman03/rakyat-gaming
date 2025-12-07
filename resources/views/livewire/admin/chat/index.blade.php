<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, On};
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Events\MessageSent;

new #[Layout('components.layouts.app'), Title('Live Chat')] class extends Component {
    public $selectedRoom = null;
    public $isRoomSelected = false;
    public $message = '';
    public $messages = [];
    public $rooms = [];

    public function mount()
    {
        $this->loadRooms();
    }

    public function loadRooms()
    {
        $this->rooms = ChatRoom::with(['user', 'latestMessage'])
            ->where('status', 'active')
            ->orderBy('last_message_at', 'desc')
            ->get()
            ->map(function ($room) {
                return [
                    'id' => $room->id,
                    'user_name' => $room->user->name,
                    'user_email' => $room->user->email,
                    'last_message' => $room->latestMessage?->message ?? 'Belum ada pesan',
                    'last_message_time' => $room->last_message_at?->diffForHumans() ?? '-',
                    'unread_count' => $room->unreadMessagesCount(),
                ];
            })
            ->toArray();
    }

    public function selectRoom($roomId)
    {
        $this->selectedRoom = ChatRoom::find($roomId);
        $this->isRoomSelected = true;
        $this->loadMessages();
        $this->markMessagesAsRead();
        $this->loadRooms();
        $this->dispatch('room-selected');
    }

    public function clearSelectedRoom()
    {
        $this->selectedRoom = null;
        $this->isRoomSelected = false;
        $this->messages = [];
    }

    public function loadMessages()
    {
        if (!$this->selectedRoom) return;

        $this->messages = ChatMessage::where('chat_room_id', $this->selectedRoom->id)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'message' => $msg->message,
                    'user_name' => $msg->user->name,
                    'is_admin' => $msg->is_admin,
                    'created_at' => $msg->created_at->format('H:i'),
                ];
            })
            ->toArray();
    }

    public function sendMessage()
    {
        if (!$this->selectedRoom) return;

        $this->validate([
            'message' => 'required|string|max:1000',
        ]);

        $chatMessage = ChatMessage::create([
            'chat_room_id' => $this->selectedRoom->id,
            'user_id' => auth()->id(),
            'message' => $this->message,
            'is_admin' => true,
            'is_read' => false,
        ]);

        $this->selectedRoom->update(['last_message_at' => now()]);

        broadcast(new MessageSent($chatMessage->load('user')))->toOthers();

        $this->message = '';
        $this->loadMessages();
        $this->loadRooms();

        $this->dispatch('message-sent');
    }

    public function markMessagesAsRead()
    {
        if (!$this->selectedRoom) return;

        ChatMessage::where('chat_room_id', $this->selectedRoom->id)
            ->where('is_admin', false)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Dispatch event to update badge count
        $this->dispatch('chatRoomUpdated');
    }

    public function closeRoom($roomId)
    {
        $room = ChatRoom::find($roomId);
        if ($room) {
            $room->update(['status' => 'closed']);
            $this->loadRooms();
            if ($this->selectedRoom && $this->selectedRoom->id == $roomId) {
                $this->selectedRoom = null;
                $this->messages = [];
            }
            Flux::toast('Chat room ditutup');
        }
    }

    public function getListeners()
    {
        $listeners = [
            'echo:admin-chat,NewChatMessage' => 'refreshRooms',
        ];

        if ($this->selectedRoom) {
            $listeners["echo-private:chat.{$this->selectedRoom->id},MessageSent"] = 'listenForMessage';
        }

        return $listeners;
    }

    public function listenForMessage($data)
    {
        $this->loadMessages();
        $this->loadRooms();
        $this->dispatch('scroll-to-bottom');
    }

    public function refreshRooms()
    {
        $this->loadRooms();
    }

    #[On('refresh-messages')]
    public function refreshMessages()
    {
        if ($this->selectedRoom) {
            $this->loadMessages();
            $this->loadRooms();
        }
    }

    public function with(): array
    {
        return [];
    }
}; ?>

<div class="flex flex-col h-[calc(100vh-8rem)]"
     x-data="{ isRoomSelected: @entangle('isRoomSelected').live }"
     x-init="
        setInterval(() => {
            if (!isRoomSelected) {
                @this.call('loadRooms');
            }
        }, 5000);
     ">
    <!-- Header -->
    <div class="mb-4 md:mb-6">
        <flux:heading size="xl">Live Chat Support</flux:heading>
        <flux:subheading>Kelola percakapan dengan customer</flux:subheading>
    </div>

    <div class="flex-1 flex flex-col lg:flex-row gap-4 lg:gap-6 overflow-hidden">
        <!-- Chat Room List -->
        <div class="w-full lg:w-80 flex flex-col {{ $selectedRoom ? 'hidden lg:flex' : 'flex' }}">
            <flux:card class="flex-1 flex flex-col overflow-hidden">
                <div class="p-3 md:p-4 border-b border-zinc-200 dark:border-zinc-700">
                    <flux:heading size="lg">Percakapan Aktif</flux:heading>
                    <div class="text-sm text-zinc-500 mt-1">{{ count($rooms) }} chat aktif</div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    @forelse ($rooms as $room)
                        <button wire:click="selectRoom({{ $room['id'] }})"
                            class="w-full p-3 md:p-4 border-b border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition {{ $selectedRoom && $selectedRoom->id == $room['id'] ? 'bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-l-indigo-600' : '' }}">
                            <div class="flex items-start gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold flex-shrink-0">
                                    {{ strtoupper(substr($room['user_name'], 0, 1)) }}
                                </div>
                                <div class="flex-1 text-left min-w-0">
                                    <div class="font-semibold text-sm truncate">{{ $room['user_name'] }}</div>
                                    <div class="text-xs text-zinc-500 truncate">{{ $room['last_message'] }}</div>
                                    <div class="text-xs text-zinc-400 mt-1">{{ $room['last_message_time'] }}</div>
                                </div>
                                @if ($room['unread_count'] > 0)
                                    <div
                                        class="bg-yellow-400 text-black text-xs rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0 font-bold">
                                        {{ $room['unread_count'] }}
                                    </div>
                                @endif
                            </div>
                        </button>
                    @empty
                        <div class="p-8 text-center text-zinc-500">
                            <svg class="w-16 h-16 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-sm">Belum ada percakapan aktif</p>
                        </div>
                    @endforelse
                </div>
            </flux:card>
        </div>

        <!-- Chat Messages -->
        <div class="flex-1 flex flex-col {{ $selectedRoom ? 'flex' : 'hidden lg:flex' }} min-h-0">
            @if ($selectedRoom)
                <div class="flex-1 flex flex-col overflow-hidden min-h-0 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                    <!-- Chat Header -->
                    <div
                        class="p-3 md:p-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between bg-white dark:bg-zinc-900 flex-shrink-0">
                        <!-- Back button for mobile -->
                        <button wire:click="clearSelectedRoom"
                            class="lg:hidden mr-2 p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <div class="flex items-center gap-2 md:gap-3 flex-1 min-w-0">
                            <div
                                class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold text-sm md:text-base flex-shrink-0">
                                {{ strtoupper(substr($selectedRoom->user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="font-semibold text-sm md:text-base truncate">{{ $selectedRoom->user->name }}</div>
                                <div class="text-xs text-zinc-500 truncate hidden sm:block">{{ $selectedRoom->user->email }}</div>
                            </div>
                        </div>
                        <flux:button wire:click="closeRoom({{ $selectedRoom->id }})" size="sm" variant="danger" class="flex-shrink-0">
                            <span class="hidden sm:inline">Tutup Chat</span>
                            <span class="sm:hidden">Tutup</span>
                        </flux:button>
                    </div>

                    <!-- Messages -->
                    <div class="flex-1 overflow-y-auto p-3 md:p-6 space-y-3 md:space-y-4 bg-zinc-50 dark:bg-zinc-800 min-h-0"
                        x-data="{
                            init() {
                                // Scroll to bottom on init
                                this.$nextTick(() => this.$refs.messageContainer.scrollTop = this.$refs.messageContainer.scrollHeight);

                                // Poll for new messages every 3 seconds
                                setInterval(() => {
                                    @this.call('refreshMessages');
                                }, 3000);
                            }
                        }"
                        x-ref="messageContainer"
                        @message-sent.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)"
                        @scroll-to-bottom.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)"
                        @room-selected.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)">
                        @forelse ($messages as $msg)
                            <div class="flex {{ $msg['is_admin'] ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-[85%] sm:max-w-[75%] md:max-w-[70%]">
                                    <div
                                        class="rounded-lg p-2.5 md:p-3 {{ $msg['is_admin'] ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white' }}">
                                        @if (!$msg['is_admin'])
                                            <div
                                                class="text-xs font-semibold mb-1 {{ $msg['is_admin'] ? 'text-white/80' : 'text-indigo-600 dark:text-indigo-400' }}">
                                                {{ $msg['user_name'] }}
                                            </div>
                                        @endif
                                        <p class="text-xs sm:text-sm break-words">{{ $msg['message'] }}</p>
                                    </div>
                                    <div
                                        class="text-xs text-zinc-500 mt-1 {{ $msg['is_admin'] ? 'text-right' : 'text-left' }}">
                                        {{ $msg['created_at'] }}
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-zinc-500 py-8">
                                <p class="text-sm">Belum ada pesan</p>
                            </div>
                        @endforelse
                    </div>

                    <!-- Input -->
                    <form wire:submit="sendMessage"
                        class="p-3 md:p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                        <div class="flex gap-2">
                            <input type="text" wire:model="message" placeholder="Ketik balasan..."
                                class="flex-1 px-3 md:px-4 py-2 text-sm md:text-base border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-zinc-800 dark:text-white"
                                maxlength="1000">
                            <flux:button type="submit" variant="primary" wire:flux:disabled="!message" class="flex-shrink-0">
                                <span class="hidden sm:inline">Kirim</span>
                                <svg class="w-5 h-5 sm:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </flux:button>
                        </div>
                        @error('message')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </form>
                </div>
            @else
                <flux:card class="flex-1 items-center justify-center hidden lg:flex">
                    <div class="text-center text-zinc-500 px-4">
                        <svg class="w-16 md:w-24 h-16 md:h-24 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <flux:heading size="lg" class="mb-2">Pilih Percakapan</flux:heading>
                        <flux:subheading>Pilih chat dari daftar untuk memulai percakapan</flux:subheading>
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>
