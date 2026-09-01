<?php

namespace App\Jobs;

use App\Models\Profile;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessProfileMediaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $profileId,
        public string $mediaPath
    ) {
        //
    }

    public function handle(): void
    {
        $profile = Profile::find($this->profileId);

        if (!$profile) {
            Log::warning(
                'Profile not found while processing media.',
                ['profile_id' => $this->profileId]
            );

            return;
        }

        try {

            if (!Storage::disk('local')->exists($this->mediaPath)) {
                Log::warning(
                    'Profile media file not found.',
                    [
                        'profile_id' => $this->profileId,
                        'media_path' => $this->mediaPath,
                    ]
                );

                return;
            }

            $profile
                ->addMediaFromDisk($this->mediaPath, 'local')
                ->toMediaCollection('profiles');
        } catch (\Throwable $e) {

            Log::error(
                'Error processing profile media.',
                [
                    'profile_id' => $this->profileId,
                    'media_path' => $this->mediaPath,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }
}
