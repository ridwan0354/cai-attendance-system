<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FaceRecognitionService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.face_recognition.url', 'http://localhost:8001');
        $this->timeout = config('services.face_recognition.timeout', 10);
    }

    /**
     * Send a frame to Python DeepFace service for recognition.
     *
     * @param  string   $base64Image  Base64-encoded image frame
     * @param  int|null $sessionId    Current session ID
     * @return array{success: bool, matches: array, faces_found: int}
     */
    public function recognize(string $base64Image, ?int $sessionId = null): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/recognize", [
                    'image'      => $base64Image,
                    'session_id' => $sessionId,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Face recognition service returned error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return ['success' => false, 'matches' => [], 'faces_found' => 0];

        } catch (\Exception $e) {
            Log::error('Face recognition service unreachable', ['error' => $e->getMessage()]);
            return ['success' => false, 'matches' => [], 'faces_found' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Register a participant's face photo to the DeepFace database.
     *
     * @param  int    $participantId
     * @param  string $name
     * @param  string $base64Image
     * @return array{success: bool}
     */
    public function registerFace(int $participantId, string $name, string $base64Image): array
    {
        try {
            $response = Http::timeout(30) // Longer timeout for registration
                ->post("{$this->baseUrl}/register", [
                    'participant_id' => $participantId,
                    'name'           => $name,
                    'image'          => $base64Image,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return ['success' => false, 'error' => $response->json('detail', 'Unknown error')];

        } catch (\Exception $e) {
            Log::error('Face registration failed', ['participant_id' => $participantId, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a participant's face from the DeepFace database.
     */
    public function deleteFace(int $participantId): bool
    {
        try {
            $response = Http::timeout($this->timeout)
                ->delete("{$this->baseUrl}/register/{$participantId}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Face deletion failed', ['participant_id' => $participantId, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Check if the Python service is healthy/running.
     */
    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->baseUrl}/health");
            return $response->successful() && $response->json('status') === 'healthy';
        } catch (\Exception) {
            return false;
        }
    }
}
