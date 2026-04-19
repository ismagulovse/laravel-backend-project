<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\DTO\AuthSuccessDTO;
use App\DTO\TokenListDTO;
use App\DTO\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        
        $user = User::whereRaw('LOWER(username) = ?', [
            strtolower($dto->username)
        ])->first();

        
        if ($user === null || !Hash::check($dto->password, $user->password)) {
            return response()->json(
                ['error' => 'Неверные учётные данные.'],
                401
            );
        }

        $tokens = $this->tokenService->createTokenPair($user);

        $authDTO = new AuthSuccessDTO(
            accessToken:  $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            user: new UserDTO(
                id:       $user->id,
                username: $user->username,
                email:    $user->email,
                birthday: $user->birthday,
            ),
        );

        return response()->json($authDTO->toArray(), 200);
    }


     public function register(RegisterRequest $request): JsonResponse
    {
        
        $dto = $request->toDTO();

        $user = User::create([
            'username' => $dto->username,
            'email'    => $dto->email,
            'password' => Hash::make($dto->password),
            'birthday' => $dto->birthday,
        ]);

        $tokens = $this->tokenService->createTokenPair($user);

        $authDTO = new AuthSuccessDTO(
            accessToken:  $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            user: new UserDTO(
                id:       $user->id,
                username: $user->username,
                email:    $user->email,
                birthday: $user->birthday,
            ),
        );

        return response()->json($authDTO->toArray(), 201);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->attributes->get('__auth_user');

        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        $userDTO = new UserDTO(
            id:       $user->id,
            username: $user->username,
            email:    $user->email,
            birthday: $user->birthday,
        );

        return response()->json($userDTO->toArray(), 200);
    }

 
   public function out(Request $request): JsonResponse
    {
        
        $tokenRecord = $request->attributes->get('__auth_token');

        $this->tokenService->revokeToken($tokenRecord);

        return response()->json(
            ['message' => 'Вы успешно вышли из системы.'],
            200
        );
    }
  
   public function tokens(Request $request): JsonResponse
    {
        $user = $request->attributes->get('__auth_user');

        $tokens = $user->activeTokens()
            ->get()
            ->map(fn($token) => [
                'id'                 => $token->id,
                'created_at'         => $token->created_at->format('Y-m-d H:i:s'),
                'access_expires_at'  => $token->access_expires_at->format('Y-m-d H:i:s'),
                'refresh_expires_at' => $token->refresh_expires_at->format('Y-m-d H:i:s'),
            ])
            ->toArray();

        $tokenListDTO = new TokenListDTO(tokens: $tokens);

        return response()->json($tokenListDTO->toArray(), 200);
    }

    public function outAll(Request $request): JsonResponse
        {
            $user = $request->attributes->get('__auth_user');

            $this->tokenService->revokeAllUserTokens($user);

            return response()->json(
                ['message' => 'Вы вышли со всех устройств.'],
                200
            );
        }
    public function refresh(Request $request): JsonResponse
        {
            try {
                $tokens = $this->tokenService->refreshTokenPair(
                    $request->input('refresh_token')
                );

                return response()->json($tokens, 200);

            } catch (\RuntimeException $e) {
                return response()->json(
                    ['error' => $e->getMessage()],
                    $e->getCode()
                );
            }
        }
}