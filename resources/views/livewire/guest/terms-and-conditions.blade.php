<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.guest')] class extends Component {
    //
}; ?>

<div class="min-h-screen py-12">
    <div class="container mx-auto px-4 max-w-5xl">
        <flux:card class="shadow-xl bg-white/95 backdrop-blur">
            <div class="p-6 md:p-10">
                <h1 class="text-3xl md:text-4xl font-bold mb-2">Syarat dan Ketentuan</h1>

                <flux:separator class="my-8" />

                <div class="prose prose-zinc max-w-none space-y-8">
                    <!-- Section 1 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">1. Selamat Datang di Rakyat Gaming</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Rakyat Gaming adalah platform digital yang menyediakan layanan top up dan kebutuhan gaming secara online untuk gamer Indonesia. Kami hadir dengan sistem yang mudah digunakan, cepat diproses, dan dirancang untuk kenyamanan pengguna.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Dengan mengakses dan menggunakan layanan Rakyat Gaming, Anda dianggap telah membaca, memahami, dan menyetujui seluruh syarat dan ketentuan di bawah ini.
                        </flux:text>
                    </section>

                    <!-- Section 2 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">2. Ruang Lingkup Layanan</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Rakyat Gaming menyediakan berbagai produk dan layanan digital terkait gaming, termasuk top up game, voucher digital, dan layanan pendukung lainnya yang tersedia di website.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Setiap layanan dirancang agar pengguna dapat langsung top up dan kembali bermain tanpa hambatan.
                        </flux:text>
                    </section>

                    <!-- Section 3 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">3. Ketentuan Pengguna</h2>
                        <ul class="list-decimal list-inside space-y-2 ml-2">
                            <li>Layanan ini dapat digunakan oleh pengguna berusia minimal 18 tahun atau dengan izin orang tua/wali.</li>
                            <li>Pengguna wajib memberikan data yang benar dan akurat, terutama terkait ID game dan detail transaksi.</li>
                            <li>Kesalahan pengisian data menjadi tanggung jawab pengguna sepenuhnya.</li>
                        </ul>
                    </section>

                    <!-- Section 4 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">4. Akun, Data & Keamanan</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Kami mengutamakan keamanan dan kenyamanan pengguna.
                            Pengguna bertanggung jawab atas seluruh aktivitas transaksi yang dilakukan menggunakan data yang dimasukkan di website Rakyat Gaming.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Rakyat Gaming berhak mengambil tindakan apabila ditemukan indikasi penyalahgunaan, manipulasi, atau aktivitas yang merugikan pihak lain.
                        </flux:text>
                    </section>

                    <!-- Section 5 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">5. Cara Transaksi di Rakyat Gaming (Anti Ribet 😎)</h2>
                        <ul class="list-decimal list-inside space-y-2 ml-2">
                            <li>Pilih game dan nominal top up</li>
                            <li>Masukkan ID game dengan benar</li>
                            <li>Selesaikan pembayaran sesuai instruksi</li>
                            <li>Pesanan diproses setelah pembayaran dikonfirmasi oleh sistem</li>
                        </ul>
                        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mt-4 rounded">
                            <flux:text class="text-sm text-gray-500">
                                💡 <strong>Tips:</strong> Sebagian besar transaksi diproses secara otomatis agar kamu bisa langsung lanjut main.
                            </flux:text>
                        </div>
                    </section>

                    <!-- Section 6 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">6. Harga & Pembayaran</h2>
                        <ul class="list-decimal list-inside space-y-2 ml-2">
                            <li>Harga produk dapat berubah sewaktu-waktu tanpa pemberitahuan sebelumnya.</li>
                            <li>Rakyat Gaming berupaya menampilkan harga yang jelas dan transparan tanpa biaya tersembunyi.</li>
                            <li>Pembayaran wajib diselesaikan sebelum layanan diproses.</li>
                        </ul>
                    </section>

                    <!-- Section 7 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">7. Waktu Proses Layanan</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Mayoritas layanan diproses dengan cepat. Namun, waktu proses dapat berbeda tergantung jenis produk, sistem game, atau kondisi teknis tertentu.
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Kami selalu berupaya memberikan pengalaman top up secepat dan senyaman mungkin.
                        </flux:text>
                    </section>

                    <!-- Section 8 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">8. Kebijakan Refund</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Pengajuan pengembalian dana hanya dapat dilakukan sesuai dengan ketentuan yang tercantum pada halaman <a href="{{ route('refund-policy') }}" wire:navigate class="text-blue-600 hover:text-blue-700 underline font-semibold">Kebijakan Pengembalian Dana Rakyat Gaming.</a>
                        </flux:text>
                        <flux:text class="leading-relaxed">
                            Setiap permohonan refund akan diverifikasi terlebih dahulu dan keputusan bersifat final.
                        </flux:text>
                    </section>

                    <!-- Section 9 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">9. Pembatalan & Penolakan Transaksi</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Rakyat Gaming berhak menolak, menunda, atau membatalkan transaksi apabila ditemukan indikasi:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Kecurangan</li>
                            <li>Penyalahgunaan layanan</li>
                            <li>Pelanggaran hukum</li>
                            <li>Aktivitas yang merugikan sistem atau pihak lain</li>
                        </ul>
                    </section>

                    <!-- Section 10 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">10. Keadaan Kahar (Force Majeure)</h2>
                        <flux:text class="leading-relaxed">
                            Rakyat Gaming tidak bertanggung jawab atas keterlambatan atau kegagalan layanan yang disebabkan oleh kejadian di luar kendali kami, termasuk namun tidak terbatas pada bencana alam, gangguan jaringan, pemadaman listrik, kebijakan pemerintah, gangguan sistem game, atau keadaan lain yang tidak dapat diprediksi.
                        </flux:text>
                    </section>

                    <!-- Section 11 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">11. Gangguan Sistem & Pihak Ketiga</h2>
                        <flux:text class="leading-relaxed">
                            Layanan Rakyat Gaming dapat dipengaruhi oleh sistem game, server publisher, atau pihak ketiga lainnya. Dalam kondisi tertentu, layanan dapat mengalami keterlambatan di luar kendali kami.
                        </flux:text>
                    </section>

                    <!-- Section 12 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">12. Batasan Tanggung Jawab</h2>
                        <flux:text class="leading-relaxed mb-3">
                            Rakyat Gaming tidak bertanggung jawab atas kerugian langsung maupun tidak langsung akibat:
                        </flux:text>
                        <ul class="list-disc list-inside space-y-2 ml-4">
                            <li>Kesalahan input data oleh pengguna</li>
                            <li>Gangguan teknis sistem</li>
                            <li>Keterlambatan layanan karena faktor eksternal</li>
                        </ul>
                    </section>

                    <!-- Section 13 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">13. Hak Kekayaan Intelektual</h2>
                        <flux:text class="leading-relaxed">
                            Seluruh konten yang terdapat di website Rakyat Gaming, termasuk teks, logo, dan desain, merupakan milik Rakyat Gaming dan dilindungi oleh hukum yang berlaku.
                        </flux:text>
                    </section>

                    <!-- Section 14 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">14. Ketentuan Lain-Lain</h2>
                        <div class="space-y-4">
                            <div>
                                <flux:text class="2">• Keseluruhan Perjanjian</flux:text>
                                <flux:text class="relaxed">
                                    Dokumen ini merupakan keseluruhan perjanjian antara pengguna dan Rakyat Gaming serta menggantikan semua kesepakatan sebelumnya.
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="2">• Pemisahan Ketentuan</flux:text>
                                <flux:text class="relaxed">
                                    Apabila ada ketentuan yang dinyatakan tidak sah, ketentuan lainnya tetap berlaku sepenuhnya.
                                </flux:text>
                            </div>
                            <div>
                                <flux:text class="2">• Pengabaian Hak</flux:text>
                                <flux:text class="relaxed">
                                    Kegagalan menegakkan suatu ketentuan tidak dianggap sebagai pengabaian hak atas ketentuan lainnya.
                                </flux:text>
                            </div>
                        </div>
                    </section>

                    <!-- Section 15 -->
                    <section>
                        <h2 class="text-2xl font-bold mb-4">15. Hukum yang Berlaku</h2>
                        <flux:text class="leading-relaxed">
                            Syarat dan Ketentuan ini diatur dan ditafsirkan berdasarkan hukum yang berlaku di Republik Indonesia.
                        </flux:text>
                    </section>
                </div>

                <flux:separator class="my-8" />

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <flux:button href="{{ route('refund-policy') }}" wire:navigate variant="outline">
                        <flux:icon.document-text class="w-5 h-5" />
                        Kebijakan Pengembalian Dana
                    </flux:button>
                    <flux:button href="/" wire:navigate variant="primary">
                        <flux:icon.home class="w-5 h-5" />
                        Kembali ke Beranda
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>
</div>
