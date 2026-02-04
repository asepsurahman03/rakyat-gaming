<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OrdersExport implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    protected $search;
    protected $statusFilter;
    protected $gameFilter;
    protected $fromDate;
    protected $toDate;

    public function __construct($search = null, $statusFilter = null, $gameFilter = null, $fromDate = null, $toDate = null)
    {
        $this->search = $search;
        $this->statusFilter = $statusFilter;
        $this->gameFilter = $gameFilter;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function query()
    {
        return Order::query()
            ->with(['user', 'game.category', 'product', 'orderPayment.paymentGateway'])
            ->when($this->search, fn($q) => $q->where('order_code', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->when($this->gameFilter, fn($q) => $q->where('game_id', $this->gameFilter))
            ->when($this->fromDate, fn($q) => $q->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($q) => $q->whereDate('created_at', '<=', $this->toDate))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Order Code',
            'Tanggal Order',
            'Status Order',
            'Harga (Rp)',
            'Nama Customer',
            'Email Customer',
            'WhatsApp',
            'Nama Game',
            'Kategori Game',
            'Nama Produk',
            'Tipe Produk',
            'Player Data',
            'Payment Method',
            'Payment Gateway',
            'Payment Status',
            'Payment Reference',
            'Payment Amount (Rp)',
            'Note',
            'Waktu Update'
        ];
    }

    public function map($order): array
    {
        // Decode player data JSON
        $playerData = '';
        if ($order->data_player) {
            $decoded = is_string($order->data_player) ? json_decode($order->data_player, true) : $order->data_player;
            if (is_array($decoded)) {
                $playerData = collect($decoded)
                    ->map(fn($value, $key) => "$key: $value")
                    ->implode(', ');
            }
        }

        return [
            $order->order_code,
            $order->created_at->format('Y-m-d H:i:s'),
            ucfirst($order->status),
            $order->price,
            $order->user?->name ?? 'Guest',
            $order->user?->email ?? $order->email ?? '-',
            $order->whatsapp ?? $order->user?->profile?->whatsapp ?? '-',
            $order->game?->name ?? '-',
            $order->game?->category?->name ?? '-',
            $order->product?->name ?? '-',
            $order->product?->product_type ?? '-',
            $playerData ?: '-',
            $order->payment_method ?? '-',
            $order->orderPayment?->paymentGateway?->name ?? '-',
            $order->orderPayment?->status ?? '-',
            $order->orderPayment?->payment_reference ?? '-',
            $order->orderPayment?->amount ?? '-',
            $order->note ?? '-',
            $order->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as header
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
