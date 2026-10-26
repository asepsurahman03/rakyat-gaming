<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.guest'), Title('Kebijakan Pengembalian Dana')] class extends Component {
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
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Kebijakan Pengembalian Dana</h1>

                <flux:separator class="my-8" />

                <div class="prose prose-zinc max-w-none space-y-8">
                    <!-- Section 1 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">1. Komitmen Kami untuk Gamer</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Rakyat Gaming hadir untuk memberikan pengalaman top up yang cepat, aman, dan transparan. Kebijakan Pengembalian Dana ini dibuat agar setiap pengguna dapat bertransaksi dengan tenang, percaya, dan tanpa rasa ragu.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Dengan melakukan transaksi di Rakyat Gaming, pengguna dianggap telah membaca, memahami, dan menyetujui kebijakan ini.
                        </flux:text>
                    </section>

                    <!-- Section 2 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">2. Prinsip Utama Transaksi</h2>
                        <ul class="list-disc list-inside space-y-2 mb-4">
                            <li>Layanan Rakyat Gaming menggunakan sistem digital yang diproses secara otomatis.</li>
                            <li>Oleh karena itu, sebagian besar transaksi bersifat final setelah pembayaran berhasil.</li>
                            <li>Kebijakan ini dibuat untuk menjaga kenyamanan gamer sekaligus keberlangsungan layanan.</li>
                        </ul>
                            <flux:text>
                                <strong>Inilah alasan banyak gamer memilih top up di Rakyat Gaming:</strong>
                                jelas dari awal, transparan, dan tanpa kejutan di akhir.
                            </flux:text>
                    </section>

                    <!-- Section 3 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">3. Kapan Refund Dapat Dipertimbangkan</h2>
                        <flux:text class="mb-3">Refund dapat dipertimbangkan secara terbatas apabila terjadi:</flux:text>
                        <ol class="list-decimal list-inside space-y-2 ml-2">
                            <li>Pembayaran berhasil namun layanan tidak diproses sama sekali oleh sistem.</li>
                            <li>Terjadi pembayaran ganda untuk transaksi yang sama.</li>
                            <li>Gangguan sistem internal yang terbukti menghambat proses layanan.</li>
                        </ol>
                        <flux:text class="leading-relaxed mt-4">
                            Setiap permohonan akan ditinjau secara adil, objektif, dan profesional oleh tim Rakyat Gaming.
                        </flux:text>
                    </section>

                    <!-- Section 4 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">4. Kondisi yang Tidak Termasuk Refund</h2>
                        <flux:text class="mb-3">
                            Untuk menjaga kecepatan dan keadilan layanan bagi seluruh pengguna, refund tidak dapat dilakukan apabila:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-2 ml-4 mb-3">
                            <li>Terjadi kesalahan pengisian data oleh pengguna (ID game, server, username, dan sejenisnya).</li>
                            <li>Layanan telah berhasil diproses atau digunakan.</li>
                            <li>Terjadi keterlambatan akibat maintenance atau gangguan server game/publisher.</li>
                            <li>Pengguna berubah keputusan setelah transaksi selesai.</li>
                        </ul>
                            <flux:text>
                            Pastikan data sudah benar sebelum pembayaran agar top up dapat diproses dengan cepat tanpa kendala.
                            </flux:text>
                    </section>

                    <!-- Section 5 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">5. Solusi Terbaik Selain Refund</h2>
                        <flux:text class="mb-3">
                            Dalam kondisi tertentu, Rakyat Gaming berhak menawarkan solusi alternatif seperti:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Penjadwalan ulang layanan</li>
                            <li>Penyesuaian teknis</li>
                            <li>Penggantian layanan dengan nilai setara</li>
                        </ul>
                        <flux:text class="leading-relaxed mt-3">
                            Langkah ini dilakukan agar pengguna tetap mendapatkan solusi tanpa harus menunggu proses refund.
                        </flux:text>
                    </section>

                    <!-- Section 6 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">6. Cara Mengajukan Permohonan</h2>
                        <flux:text class="mb-4">
                            Permohonan hanya dapat diajukan melalui kontak resmi Rakyat Gaming dengan melampirkan bukti transaksi yang valid:
                        </flux:text>
                            <ul class="space-y-3">
                                <li>
                                    <strong>Email</strong> : <a href="mailto:{{ $settings['contact_email'] }}" class="link link-hover">{{ $settings['contact_email'] }}</a>
                                </li>
                                <li>
                                    <strong>WhatsApp</strong> : <a href="{{ $settings['url_whatsapp'] }}" target="_blank"
                                        class="link link-hover">+62{{ $settings['contact_whatsapp'] }}</a>
                                </li>
                                <li>
                                    <strong>Jam Operasional</strong> : 09.00 – 21.00 WIB
                                </li>
                            </ul>
                        <flux:text class="leading-relaxed mt-4 text-sm italic">
                            Permohonan yang diajukan di luar jalur resmi tidak akan diproses.
                        </flux:text>
                    </section>

                    <!-- Section 7 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">7. Batas Waktu Pengajuan</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Permohonan refund harus diajukan <strong>maksimal 1 x 24 jam</strong> sejak transaksi dilakukan.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Setelah melewati batas waktu tersebut, transaksi dianggap selesai dan tidak dapat diproses lebih lanjut.
                        </flux:text>
                    </section>

                    <!-- Section 8 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">8. Proses & Keputusan</h2>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Setiap permohonan akan melalui proses verifikasi menyeluruh.</li>
                            <li>Keputusan akhir berada di pihak Rakyat Gaming dan bersifat final serta mengikat.</li>
                            <li>Apabila refund disetujui, proses pengembalian dana dapat memerlukan waktu hingga 14 hari kerja.</li>
                        </ul>
                    </section>

                    <!-- Section 9 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">9. Keterkaitan dengan Syarat & Ketentuan</h2>
                        <flux:text class="leading-relaxed">
                            Kebijakan ini merupakan acuan resmi sebagaimana dimaksud dalam Point 8 <a href="{{ route('terms-and-conditions') }}" wire:navigate class="text-blue-600 hover:text-blue-700 underline font-semibold">Syarat & Ketentuan Layanan Rakyat Gaming</a> dan menjadi bagian yang tidak terpisahkan darinya.
                        </flux:text>
                    </section>
                </div>

                <flux:separator class="my-8" />
            </div>
        </flux:card>
    </div>
</div>
