<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $status;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $status)
    {
        $this->order = $order;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Update Status Pesanan #' . $this->order->order_code)
            ->greeting('Halo ' . ($notifiable->name ?? 'Customer') . '!');

        switch ($this->status) {
            case 'pending':
                $message->line('Pesanan Anda telah dibuat dan menunggu pembayaran.')
                    ->line('**Detail Pesanan:**')
                    ->line('Kode Pesanan: **' . $this->order->order_code . '**')
                    ->line('Produk: **' . $this->order->product->name . '**')
                    ->line('Total: **Rp ' . number_format($this->order->total_price, 0, ',', '.') . '**')
                    ->line('Silakan lakukan pembayaran sebelum ' . $this->order->expired_at?->format('d M Y H:i') . ' WIB')
                    ->action('Bayar Sekarang', route('checkout', $this->order->order_code));
                break;

            case 'process':
                $message->line('Pembayaran Anda telah diterima dan pesanan sedang diproses.')
                    ->line('**Detail Pesanan:**')
                    ->line('Kode Pesanan: **' . $this->order->order_code . '**')
                    ->line('Produk: **' . $this->order->product->name . '**')
                    ->line('Pesanan Anda akan segera diproses secara otomatis.')
                    ->action('Cek Status Pesanan', route('cek-transaksi'));
                break;

            case 'success':
                $message->line('🎉 Pesanan Anda telah berhasil diproses!')
                    ->line('**Detail Pesanan:**')
                    ->line('Kode Pesanan: **' . $this->order->order_code . '**')
                    ->line('Produk: **' . $this->order->product->name . '**')
                    ->line('Status: **Berhasil**')
                    ->line('Terima kasih telah berbelanja di ' . config('app.name') . '!')
                    ->action('Lihat Pesanan', route('cek-transaksi'));
                break;

            case 'failed':
                $message->line('❌ Maaf, pesanan Anda gagal diproses.')
                    ->line('**Detail Pesanan:**')
                    ->line('Kode Pesanan: **' . $this->order->order_code . '**')
                    ->line('Produk: **' . $this->order->product->name . '**')
                    ->line('Silakan hubungi customer support kami untuk bantuan lebih lanjut.')
                    ->action('Hubungi Support', 'https://wa.me/' . str_replace(['+', ' ', '-'], '', env('CONTACT_WHATSAPP')));
                break;
        }

        return $message->salutation('Salam, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_code' => $this->order->order_code,
            'status' => $this->status,
        ];
    }
}
