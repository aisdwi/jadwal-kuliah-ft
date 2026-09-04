<?php

namespace App\Modules\Penjadwalan\Application\Service;

use App\Modules\Shared\Application\Service\NotificationWriter;

final class SchedulingGenerationNotifier
{
    private const ALL_DATA_LABEL = 'Semua Data';

    public function __construct(private readonly NotificationWriter $notificationWriter) {}

    public function queued(string $userId, ?string $semesterTipe, array $scope): void
    {
        $this->notificationWriter->createForUser(
            userId: $userId,
            type: 'schedule_generation_queued',
            title: 'Generate jadwal dimulai',
            message: 'Proses generate jadwal masuk antrean background.',
            data: $this->notificationData($semesterTipe, $scope, 'queued'),
            actorId: $userId,
        );
    }

    public function canceled(string $userId, array $previous): void
    {
        $this->notificationWriter->createForUser(
            userId: $userId,
            type: 'schedule_generation_canceled',
            title: 'Generate jadwal dibatalkan',
            message: 'Proses generate jadwal otomatis telah dibatalkan.',
            data: $this->notificationData(null, $previous, 'canceled'),
            actorId: $userId,
        );
    }

    public function completed(string $userId, ?string $semesterTipe, array $scope, string $status, array $result): void
    {
        $this->notificationWriter->createForUser(
            userId: $userId,
            type: match ($status) {
                'completed' => 'schedule_generation_completed',
                'canceled' => 'schedule_generation_canceled',
                default => 'schedule_generation_failed',
            },
            title: match ($status) {
                'completed' => 'Generate jadwal selesai',
                'canceled' => 'Generate jadwal dibatalkan',
                default => 'Generate jadwal belum berhasil',
            },
            message: $this->completionMessage($status, $result),
            data: $this->notificationData($semesterTipe, $scope, $status, $result),
            actorId: $userId,
        );
    }

    public function failed(string $userId, ?string $semesterTipe, array $scope, string $message): void
    {
        $this->notificationWriter->createForUser(
            userId: $userId,
            type: 'schedule_generation_failed',
            title: 'Generate jadwal gagal',
            message: $message,
            data: $this->notificationData($semesterTipe, $scope, 'failed'),
            actorId: $userId,
        );
    }

    private function completionMessage(string $status, array $result): string
    {
        if ($status === 'completed') {
            $savedCount = count($result['results'] ?? []);

            return $savedCount > 0
                ? "{$savedCount} kelas berhasil dijadwalkan."
                : 'Generate jadwal selesai diproses.';
        }

        if ($status === 'canceled') {
            return 'Generate jadwal dibatalkan oleh pengguna.';
        }

        return $result['message'] ?? 'Generate jadwal tidak menghasilkan solusi yang dapat dipakai.';
    }

    private function notificationData(?string $semesterTipe, array $scope, string $status, array $result = []): array
    {
        return [
            'url' => '/scheduling/auto',
            'status' => $status,
            'semester_tipe' => $semesterTipe,
            'scope_label' => $scope['label'] ?? self::ALL_DATA_LABEL,
            'generation' => $result['generation'] ?? null,
            'best_fitness' => $result['best_fitness'] ?? $result['fitness'] ?? null,
        ];
    }
}
