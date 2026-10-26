<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use App\Models\User;
use Carbon\Carbon;

class MqttPresensiListener extends Command
{
    protected $signature = 'mqtt:listen-presensi';
    protected $description = 'Mendengarkan data scan RFID ESP32 via MQTT';

    public function handle()
    {
        $server   = '127.0.0.1'; // Karena berjalan langsung di VPS lokal
        $port     = 1883;
        $clientId = 'laravel-listener-' . uniqid();

        $mqtt = new MqttClient($server, $port, $clientId);

        $this->info("Menghubungkan ke MQTT Broker...");
        $mqtt->connect();
        $this->info("Berhasil terhubung! Mendengarkan topik presensi...");

        // Mendengarkan topik scan dari semua alat
        // Format topik pengiriman ESP32: muttaqin/{device_id}/scan
        $mqtt->subscribe('muttaqin/+/scan', function ($topic, $message) use ($mqtt) {
            $this->info("Menerima data dari topik [{$topic}]: {$message}");

            $data = json_decode($message, true);
            $rfidUid  = $data['rfid_uid'] ?? null;
            $deviceId = $data['device_id'] ?? 'device01';

            if (!$rfidUid) {
                return;
            }

            // 1. Logika Pencarian Data (Cek User/Guru atau Santri)
            $user = User::where('rfid_uid', strtoupper(trim($rfidUid)))->first();

            $statusScan = 'FAIL';
            $namaTampil = 'Tidak Terdaftar';
            $pesanLcd   = 'Hubungi Admin';

            if ($user) {
                $statusScan = 'SUCCESS';
                // Batasi nama maksimal 16 karakter agar pas di baris LCD 16x2
                $namaTampil = substr($user->name, 0, 16);
                $pesanLcd   = 'Berhasil Hadir';

                // TODO: Nanti kita pasang logika insert presensi masuk/pulang ke database di sini
            }

            // 2. Balas pesan balik ke topik spesifik perangkat tersebut
            // Format topik respon: muttaqin/{device_id}/response
            $responseTopic = "muttaqin/{$deviceId}/response";
            $payloadResponse = json_encode([
                'status'  => $statusScan,
                'name'    => $namaTampil,
                'message' => $pesanLcd,
            ]);

            $mqtt->publish($responseTopic, $payloadResponse, 0);
            $this->info("Membalas ke [{$responseTopic}]: {$payloadResponse}");
        }, 0);

        // Menjaga proses agar terus mendengarkan (infinite loop)
        $mqtt->loop(true);
    }
}