<?php

declare(strict_types=1);

// app/Services/TokenService.php

namespace App\Services;

use App\Models\Token;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

class TokenService implements TokenServiceInterface
{
    
    private int $accessTtl;
    private int $refreshTtl;
    private int $maxTokens;

    public function __construct()
    {
        $this->accessTtl  = (int) env('ACCESS_TOKEN_TTL', 60);
        $this->refreshTtl = (int) env('REFRESH_TOKEN_TTL', 10080);
        $this->maxTokens  = (int) env('MAX_ACTIVE_TOKENS', 5);
    }

    public function createTokenPair(User $user): array
    {
        $this->enforceTokenLimit($user);

        $accessToken  = $this->generateSignedToken($user->id, 'access');
        $refreshToken = $this->generateSignedToken($user->id, 'refresh');

        Token::create([
            'user_id'             => $user->id,
            'access_token_hash'   => $this->hashToken($accessToken),
            'refresh_token_hash'  => $this->hashToken($refreshToken),
            'is_revoked'          => false,
            'refresh_used'        => false,
            'access_expires_at'   => now()->addMinutes($this->accessTtl),
            'refresh_expires_at'  => now()->addMinutes($this->refreshTtl),
        ]);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
        ];
    }

    private function generateSignedToken(int $userId, string $type): string
    {
       
        $header = base64_encode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT',
        ]));

        $payload = base64_encode(json_encode([
            'uid'  => $userId,      
            'jti' => Str::random(32),              
            'exp'  => $type === 'access'          
                ? now()->addMinutes($this->accessTtl)->timestamp
                : now()->addMinutes($this->refreshTtl)->timestamp,
        ]));
        $signature = base64_encode(
            hash_hmac('sha256', $header . '.' . $payload, $this->getSecret(), true)
        );

        return $header . '.' . $payload . '.' . $signature;
    }

    private function verifySignature(string $token): bool
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $receivedSignature] = $parts;

        $expectedSignature = base64_encode(
            hash_hmac('sha256', $header . '.' . $payload, $this->getSecret(), true)
        );

        return hash_equals($expectedSignature, $receivedSignature);
    }

  
    public function validateAccessToken(string $token): ?Token
    {

        if (!$this->verifySignature($token)) {
            return null;
        }

        return Token::where('access_token_hash', $this->hashToken($token))
            ->where('is_revoked', false)
            ->where('access_expires_at', '>', now())
            ->first();
    }

    public function validateRefreshToken(string $token): ?Token
    {
        if (!$this->verifySignature($token)) {
            return null;
        }

        return Token::where('refresh_token_hash', $this->hashToken($token))
            ->where('is_revoked', false)
            ->where('refresh_expires_at', '>', now())
            ->first();
    }

    public function revokeToken(Token $token): void
    {
        $token->update(['is_revoked' => true]);
    }

    public function revokeAllUserTokens(User $user): void
    {
        $user->tokens()->update(['is_revoked' => true]);
    }


    public function refreshTokenPair(string $refreshToken): array
    {
        $tokenRecord = $this->validateRefreshToken($refreshToken);

        if ($tokenRecord === null) {
            throw new RuntimeException('Invalid or expired refresh token.', 401);
        }

        if ($tokenRecord->refresh_used) {

            $this->revokeAllUserTokens($tokenRecord->user);
            throw new RuntimeException('Refresh token reuse detected. All tokens revoked.', 401);
        }

        $tokenRecord->update([
            'refresh_used' => true,
            'is_revoked'   => true,
        ]);

        return $this->createTokenPair($tokenRecord->user);
    }

  
    private function enforceTokenLimit(User $user): void
    {
        $activeTokens = $user->activeTokens()
            ->orderBy('created_at', 'asc')
            ->get();

        if ($activeTokens->count() >= $this->maxTokens) {

            $this->revokeToken($activeTokens->first());
        }
    }


    public function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    private function getSecret(): string
    {
        return (string) env('TOKEN_SECRET', config('app.key'));
    }
}