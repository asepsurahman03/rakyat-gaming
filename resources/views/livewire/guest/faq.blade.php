<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.guest'), Title('Pertanyaan yang Sering Ditanyakan')] class extends Component {
    public $settings;

    public function mount()
    {
        $this->settings = [
            'contact_email' => env('CONTACT_EMAIL', \App\Models\Setting::get('contact_email', 'codetech.id@gmail.com')),
            'contact_phone' => env('CONTACT_PHONE', '85351221602'),
            'contact_whatsapp' => env('CONTACT_WHATSAPP', '85351221602'),
            'url_whatsapp' => env('CONTACT_WHATSAPP') ? 'https://wa.me/+62' . ltrim(preg_replace('/\D/', '', env('CONTACT_WHATSAPP')), '0') : null,
        ];
    }
}; ?>

<div class="min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-5xl">
        <flux:card class="shadow-xl bg-white/95 backdrop-blur">
            <div class="p-6 md:p-10">
                <flux:heading class="text-3xl md:text-4xl font-bold mb-2">Pertanyaan yang Sering Ditanyakan</flux:heading>
                <flux:text class="mb-6">Baca 2 Menit. Banyak Gamer Langsung Top Up Setelah Ini.</flux:text>

                <flux:separator class="my-8" />

                <div class="prose prose-zinc max-w-none space-y-8">
                    <!-- Question 1 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">1. Apa itu Rakyat Gaming?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Tempat top up game yang dipilih banyak gamer Indonesia karena prosesnya cepat, alurnya jelas, dan tidak bikin ribet. Dirancang untuk kamu yang ingin langsung lanjut main setelah bayar, tanpa drama teknis atau ketidakjelasan transaksi.
                        </flux:text>
                    </section>

                    <!-- Question 2 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">2. Kenapa banyak gamer memilih top up di Rakyat Gaming?</flux:heading>
                        <flux:text class="leading-relaxed mb-3">
                            Karena pengguna tidak perlu menebak-nebak proses transaksi.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Mulai dari pemilihan produk, pembayaran, hingga pemrosesan, semuanya berjalan sesuai alur yang jelas. Inilah yang membuat banyak gamer merasa nyaman dan kembali menggunakan Rakyat Gaming.
                        </flux:text>
                    </section>

                    <!-- Question 3 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">3. Seberapa cepat proses top up di sini?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Sebagian besar transaksi diproses secara otomatis segera setelah pembayaran dikonfirmasi. Dalam banyak kasus, pengguna dapat langsung melanjutkan permainan tanpa harus menunggu bantuan manual.
                        </flux:text>
                    </section>

                    <!-- Question 4 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">4. Apakah top up di Rakyat Gaming aman?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Rakyat Gaming menggunakan sistem digital yang dirancang untuk menjaga keamanan transaksi dan kenyamanan pengguna. Selama data yang dimasukkan benar, proses dapat berjalan dengan lancar dan terkendali.
                        </flux:text>
                    </section>

                    <!-- Question 5 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">5. Mengapa pengisian ID game harus benar?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Karena sistem bekerja secara otomatis. Kesalahan pengisian ID atau server dapat menyebabkan layanan tidak dapat diproses atau dikirim ke akun yang tidak sesuai. Memastikan data benar adalah langkah penting agar top up berjalan tanpa kendala.
                        </flux:text>
                    </section>

                    <!-- Question 6 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">6. Apa yang harus dilakukan jika top up belum diterima?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Pengguna disarankan menunggu beberapa saat. Jika layanan belum diterima, silakan menghubungi layanan pelanggan Rakyat Gaming dengan menyertakan bukti transaksi untuk dilakukan pengecekan sesuai prosedur.
                        </flux:text>
                    </section>

                    <!-- Question 7 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">7. Apakah transaksi bisa dibatalkan atau direfund?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Sebagian besar transaksi bersifat final. Pengembalian dana hanya dapat dipertimbangkan dalam kondisi tertentu sesuai dengan Kebijakan Pengembalian Dana Rakyat Gaming. Ketentuan ini dibuat agar proses tetap cepat dan adil bagi semua pengguna.
                        </flux:text>
                    </section>

                    <!-- Question 8 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">8. Berapa lama proses pengembalian dana jika disetujui?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Jika pengembalian dana disetujui, proses dapat memerlukan waktu hingga 14 hari kerja sesuai dengan hasil verifikasi dan prosedur yang berlaku.
                        </flux:text>
                    </section>

                    <!-- Question 9 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">9. Bagaimana cara menghubungi layanan pelanggan?</flux:heading>
                        <flux:text class="leading-relaxed mb-3">
                            Pengguna dapat menghubungi Rakyat Gaming melalui kontak resmi berikut:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li><strong>Email:</strong> <a href="{{ 'mailto:' . $settings['contact_email'] }}">{{ $settings['contact_email'] }}</a></li>
                            <li><strong>WhatsApp:</strong><a href="{{ $settings['url_whatsapp'] }}" target="_blank"
                                        class="link link-hover">+62{{ $settings['contact_whatsapp'] }}</a></li>
                            <li><strong>Jam operasional:</strong> 09.00 – 21.00 WIB</li>
                        </ul>
                        <flux:text class="leading-relaxed mt-3">
                            Permohonan di luar jalur resmi tidak dapat diproses.
                        </flux:text>
                    </section>

                    <!-- Question 10 -->
                    <section>
                        <flux:heading class="text-2xl font-bold mb-4">10. Siapa yang paling cocok menggunakan Rakyat Gaming?</flux:heading>
                        <flux:text class="leading-relaxed">
                            Rakyat Gaming cocok bagi pengguna yang tidak ingin menunggu lama, menghindari proses rumit, dan mengutamakan kejelasan transaksi. Jika Anda menghargai kecepatan dan kepastian, layanan ini dirancang untuk Anda.
                        </flux:text>
                    </section>
                </div>

                <flux:separator class="my-8" />

                <div class="text-center">
                    <flux:text class="">
                        Masih ada pertanyaan? Hubungi kami melalui WhatsApp atau email yang tertera di atas.
                    </flux:text>
                </div>
            </div>
        </flux:card>
    </div>
</div>
