<?php

use Livewire\Volt\Component;
use Livewire\Attributes\On;
use App\Models\ChatRoom;
use App\Models\ChatMessage;
use App\Events\MessageSent;
use App\Events\NewChatMessage;

new class extends Component {
    public $isOpen = false;
    public $message = '';
    public $chatRoom = null;
    public $messages = [];
    public $pollingEnabled = false;
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadChatRoom();
        $this->loadUnreadCount();
    }

    public function loadChatRoom()
    {
        if (auth()->check()) {
            $this->chatRoom = ChatRoom::firstOrCreate(
                ['user_id' => auth()->id(), 'status' => 'active'],
                ['last_message_at' => now()]
            );
        }

        if ($this->chatRoom) {
            $this->loadMessages();
        }
    }

    public function loadMessages()
    {
        if (!$this->chatRoom) return;

        $this->messages = ChatMessage::where('chat_room_id', $this->chatRoom->id)
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

    public function loadUnreadCount()
    {
        if (!auth()->check() || !$this->chatRoom) {
            $this->unreadCount = 0;
            return;
        }

        $this->unreadCount = ChatMessage::where('chat_room_id', $this->chatRoom->id)
            ->where('is_admin', true)
            ->where('is_read', false)
            ->count();
    }

    public function toggleChat()
    {
        if (!auth()->check()) {
            Flux::toast('Silakan login terlebih dahulu untuk menggunakan live chat', variant: 'warning');
            return;
        }

        $this->isOpen = !$this->isOpen;

        if ($this->isOpen) {
            $this->loadChatRoom();
            $this->markMessagesAsRead();
            $this->unreadCount = 0;
            $this->pollingEnabled = true;
            $this->dispatch('chat-opened');
        } else {
            $this->pollingEnabled = false;
        }
    }

    public function sendMessage()
    {
        if (!auth()->check()) {
            Flux::toast('Silakan login terlebih dahulu', variant: 'warning');
            return;
        }

        $this->validate([
            'message' => 'required|string|max:1000',
        ]);

        if (!$this->chatRoom) {
            $this->loadChatRoom();
        }

        $chatMessage = ChatMessage::create([
            'chat_room_id' => $this->chatRoom->id,
            'user_id' => auth()->id(),
            'message' => $this->message,
            'is_admin' => false,
        ]);

        $this->chatRoom->update(['last_message_at' => now()]);

        broadcast(new MessageSent($chatMessage->load('user')))->toOthers();
        broadcast(new NewChatMessage());

        $this->message = '';
        $this->loadMessages();

        $this->dispatch('message-sent');
    }

    public function markMessagesAsRead()
    {
        if (!$this->chatRoom) return;

        ChatMessage::where('chat_room_id', $this->chatRoom->id)
            ->where('is_admin', true)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function getListeners()
    {
        if (!$this->chatRoom) {
            return [];
        }

        return [
            "echo-private:chat.{$this->chatRoom->id},MessageSent" => 'listenForMessage',
        ];
    }

    public function listenForMessage($data)
    {
        $this->loadMessages();
        if (!$this->isOpen) {
            $this->loadUnreadCount();
        }
        $this->dispatch('scroll-to-bottom');
    }

    #[On('refresh-messages')]
    public function refreshMessages()
    {
        if ($this->isOpen && $this->chatRoom) {
            $this->loadMessages();
        } elseif ($this->chatRoom) {
            $this->loadUnreadCount();
        }
    }

    public function with(): array
    {
        return [];
    }
}; ?>

<div x-data x-init="setInterval(() => { @this.call('loadUnreadCount'); }, 3000)">
    <!-- Chat Button (Always visible) -->
    <div class="fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50">
        @if (!$isOpen)
            <button wire:click="toggleChat"
                class="bg-yellow-400 text-white rounded-full p-3 sm:p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 group">
                <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                @if($unreadCount > 0)
                    <span
                        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[20px] h-5 px-1.5 flex items-center justify-center font-bold animate-pulse">
                        {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                    </span>
                @endif
            </button>
        @endif
    </div>

    <!-- Chat Window (Only for authenticated users) -->
    @if (auth()->check() && $isOpen)
        <div class="fixed inset-x-4 bottom-4 sm:inset-x-auto sm:bottom-6 sm:right-6 sm:w-96 h-[calc(100vh-2rem)] sm:h-[600px] max-h-[700px] bg-white dark:bg-zinc-900 rounded-lg shadow-2xl z-50 flex flex-col overflow-hidden border border-zinc-200 dark:border-zinc-700"
            x-data="{
                atBottom: true,
                init() {
                    // Poll for new messages every 3 seconds
                    if (@js($pollingEnabled)) {
                        setInterval(() => {
                            @this.call('refreshMessages');
                        }, 3000);
                    }
                }
            }"
            @message-sent.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)"
            @scroll-to-bottom.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)"
            @chat-opened.window="$nextTick(() => $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight)">

                <!-- Header -->
                <div
                    class="bg-gradient-to-bl from-[#7E0000] to-[#470000] text-white p-3 sm:p-4 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white/20 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <span class="absolute bottom-0 right-0 w-2 h-2 sm:w-3 sm:h-3 bg-green-400 rounded-full border-2 border-white"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="font-semibold text-sm sm:text-base truncate">Customer Support</div>
                            <div class="text-xs text-white/80">Online</div>
                        </div>
                    </div>
                    <button wire:click="toggleChat" class="hover:bg-white/10 rounded p-1 transition flex-shrink-0">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-3 sm:p-4 space-y-3 sm:space-y-4 bg-zinc-50 dark:bg-zinc-800" x-ref="messageContainer"
                    @scroll="atBottom = ($event.target.scrollHeight - $event.target.scrollTop === $event.target.clientHeight)">
                    @forelse ($messages as $msg)
                        <div class="flex {{ $msg['is_admin'] ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[85%] sm:max-w-[75%]">
                                <div
                                    class="rounded-lg p-2.5 sm:p-3 {{ $msg['is_admin'] ? 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white' : 'bg-gradient-to-bl from-[#7E0000] to-[#470000] text-white' }}">
                                    @if ($msg['is_admin'])
                                        <div class="text-xs font-semibold mb-1 text-indigo-600 dark:text-indigo-400">
                                            {{ $msg['user_name'] }}
                                        </div>
                                    @endif
                                    <p class="text-xs sm:text-sm break-words">{{ $msg['message'] }}</p>
                                </div>
                                <div
                                    class="text-xs text-zinc-500 mt-1 {{ $msg['is_admin'] ? 'text-left' : 'text-right' }}">
                                    {{ $msg['created_at'] }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-500 py-6 sm:py-8 px-4">
                            <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-xs sm:text-sm">Mulai percakapan dengan admin</p>
                        </div>
                    @endforelse
                </div>

                <!-- Input -->
                <form wire:submit="sendMessage" class="p-3 sm:p-4 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-700 flex-shrink-0" x-data="{ canSend: false }">
                    <div class="flex gap-2">
                        <input type="text"
                            wire:model="message"
                            @input="canSend = $event.target.value.trim().length > 0"
                            placeholder="Ketik pesan..."
                            class="flex-1 px-3 sm:px-4 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:bg-zinc-800 dark:text-white"
                            maxlength="1000">
                        <button type="submit"
                            class="bg-yellow-400 text-white px-3 sm:px-4 py-2 rounded-lg hover:shadow-lg transition-all duration-300 disabled:opacity-50 flex-shrink-0"
                            :disabled="!canSend">
                            <flux:icon.send-horizontal class="w-5 h-5" />
                        </button>
                    </div>
                    @error('message')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </form>
            </div>
        @endif
    </div>
</div>
