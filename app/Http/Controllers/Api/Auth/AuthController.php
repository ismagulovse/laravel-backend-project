<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\DTO\AuthSuccessDTO;
use App\DTO\RoleDTO;
use App\DTO\TokenListDTO;
use App\DTO\UserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\TokenServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $user->load('roles');

        $authDTO = new AuthSuccessDTO(
            accessToken:  $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            user: new UserDTO(
                id:       $user->id,
                username: $user->username,
                email:    $user->email,
                birthday: $user->birthday,
                roles:    $this->mapRoleDTOs($user),
            ),
        );

        return response()->json($authDTO->toArray(), 200);
    }


    public function register(RegisterRequest $request): JsonResponse
    {
        
        $dto = $request->toDTO();

        $user = DB::transaction(function () use ($dto): User {
            $user = User::create([
                'username' => $dto->username,
                'email'    => $dto->email,
                'password' => Hash::make($dto->password),
                'birthday' => $dto->birthday,
            ]);

            // После регистрации автоматически назначаем базовую роль guest.
            $guestRole = Role::query()->where('slug', 'guest')->first();
            if ($guestRole !== null) {
                $link = UserRole::query()
                    ->withTrashed()
                    ->where('user_id', $user->id)
                    ->where('role_id', $guestRole->id)
                    ->first();

                if ($link === null) {
                    UserRole::query()->create([
                        'user_id'    => (int) $user->id,
                        'role_id'    => (int) $guestRole->id,
                        'created_by' => (int) $user->id,
                    ]);
                } elseif ($link->trashed()) {
                    $link->restore();
                    $link->deleted_by = null;
                    $link->save();
                }
            }

            return $user;
        });

        $tokens = $this->tokenService->createTokenPair($user);
        $user->load('roles');

        $authDTO = new AuthSuccessDTO(
            accessToken:  $tokens['access_token'],
            refreshToken: $tokens['refresh_token'],
            user: new UserDTO(
                id:       $user->id,
                username: $user->username,
                email:    $user->email,
                birthday: $user->birthday,
                roles:    $this->mapRoleDTOs($user),
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
            roles:    $this->mapRoleDTOs($user->loadMissing('roles')),
        );

        return response()->json($userDTO->toArray(), 200);
    }

    public function permissions(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->attributes->get('__auth_user');

        if ($user === null) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        $user->loadMissing('roles.permissions');

        $permissions = $user->roles
            ->flatMap(fn ($role) => $role->permissions->map(
                fn ($permission): array => [
                    'id' => (int) $permission->id,
                    'name' => (string) $permission->name,
                    'slug' => (string) $permission->slug,
                    'description' => $permission->description,
                ]
            ))
            ->unique('slug')
            ->sortBy('slug')
            ->values()
            ->all();

        return response()->json([
            'user_id' => (int) $user->id,
            'permissions' => $permissions,
        ], 200);
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

    /**
     * @return RoleDTO[]
     */
    private function mapRoleDTOs(User $user): array
    {
        $priority = [
            'admin' => 1,
            'user' => 2,
            'guest' => 3,
        ];

        return $user->roles
            // Выводим в приоритетном порядке: Admin -> User -> Guest.
            ->sortBy(static fn ($role): int => $priority[$role->slug] ?? 99)
            ->map(static fn ($role): RoleDTO => new RoleDTO(
                id: (int) $role->id,
                name: (string) $role->name,
                slug: (string) $role->slug,
                description: $role->description,
                createdAt: $role->created_at,
                createdBy: (int) $role->created_by,
                deletedAt: $role->deleted_at,
                deletedBy: $role->deleted_by !== null ? (int) $role->deleted_by : null,
            ))
            ->values()
            ->all();
    }
}
