<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\DeploymentFailedException;
use App\Exceptions\DeploymentInProgressException;
use App\Http\Controllers\Controller;
use App\Services\DeploymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GitWebhookController extends Controller
{
    public function __construct(
        private readonly DeploymentServiceInterface $deployment,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        if (! $this->secretIsValid($request->input('secret_key'))) {
            return response()->json(
                ['message' => 'Invalid secret key'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $result = $this->deployment->deploy($request->ip());
        } catch (DeploymentInProgressException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                Response::HTTP_CONFLICT,
            );
        } catch (DeploymentFailedException $e) {
            return response()->json(
                ['message' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return response()->json([
            'message' => 'Deployment finished',
            'result'  => $result,
        ]);
    }

    /**
     * Регистрозависимое сравнение секрета в постоянном времени 
     */
    private function secretIsValid(mixed $provided): bool
    {
        $expected = config('git.webhook_secret');

        if (! is_string($expected) || $expected === '' || ! is_string($provided)) {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}
