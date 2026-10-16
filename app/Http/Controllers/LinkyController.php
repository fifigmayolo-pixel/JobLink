<?php

namespace App\Http\Controllers;

use App\Services\LinkyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class LinkyController extends Controller
{
    // Free-tier quota is shared by everyone, so each person gets a daily cap.
    private const DAILY_LIMIT = 40;

    public function chat(Request $request, LinkyService $linky)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['required', 'in:user,model'],
            'history.*.text' => ['required', 'string', 'max:1500'],
        ]);

        $user = $request->user();
        $limiterKey = 'linky-daily:'.$user->id;

        if (RateLimiter::tooManyAttempts($limiterKey, self::DAILY_LIMIT)) {
            return response()->json([
                'reply' => "You've reached today's chat limit. Please try again tomorrow, or contact the Job Coordinator's office.",
            ], 429);
        }

        RateLimiter::hit($limiterKey, 86400);

        $result = $linky->ask(
            trim($data['message']),
            $this->cleanHistory($data['history'] ?? []),
            (string) $user->role,
        );

        if ($result['text'] !== null) {
            return response()->json(['reply' => $result['text']]);
        }

        if ($result['reason'] === 'busy') {
            return response()->json([
                'reply' => "I'm getting a lot of questions right now. Please try again in a minute.",
            ], 429);
        }

        return response()->json([
            'reply' => "Sorry, I can't answer right now. Please try again later, or contact the Job Coordinator's office.",
        ], 503);
    }

    /**
     * Gemini wants turns that alternate user / model, starting with user, and
     * ending on a model turn (the new message is the next user turn).
     */
    private function cleanHistory(array $history): array
    {
        $clean = [];

        foreach ($history as $turn) {
            $expected = count($clean) % 2 === 0 ? 'user' : 'model';

            if ($turn['role'] !== $expected) {
                continue;
            }

            $clean[] = ['role' => $turn['role'], 'text' => trim($turn['text'])];
        }

        if ($clean && $clean[array_key_last($clean)]['role'] === 'user') {
            array_pop($clean);
        }

        return $clean;
    }
}