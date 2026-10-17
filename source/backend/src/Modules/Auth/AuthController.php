<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Auth;

use WealthFlow\Core\Config;
use WealthFlow\Core\Database;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class AuthController
{
    private ?AuthService $authService = null;
    private ?Config $config = null;

    public function __construct(?AuthService $authService = null, ?Config $config = null)
    {
        $this->authService = $authService;
        $this->config = $config;
    }

    private function getAuthService(): AuthService
    {
        if ($this->authService === null) {
            /** @var Config $cfg */
            $cfg = $this->config ?? (require dirname(__DIR__, 3) . '/bootstrap.php');
            $this->authService = new AuthService(Database::getConnection(), $cfg);
        }
        return $this->authService;
    }

    public function register(Request $request): void
    {
        $result = $this->getAuthService()->register($request->getBody());
        JsonResponse::success($result, 201);
    }

    public function login(Request $request): void
    {
        $result = $this->getAuthService()->login($request->getBody());
        JsonResponse::success($result, 200);
    }

    public function me(Request $request): void
    {
        $result = $this->getAuthService()->me();
        JsonResponse::success($result, 200);
    }

    public function refresh(Request $request): void
    {
        $result = $this->getAuthService()->refresh();
        JsonResponse::success($result, 200);
    }

    public function logout(Request $request): void
    {
        $result = $this->getAuthService()->logout();
        JsonResponse::success($result, 200);
    }

    public function changePassword(Request $request): void
    {
        $result = $this->getAuthService()->changePassword($request->getBody());
        JsonResponse::success($result, 200);
    }

    public function forgotPassword(Request $request): void
    {
        $result = $this->getAuthService()->requestPasswordReset($request->getBody());
        JsonResponse::success($result, 200);
    }

    public function resetPassword(Request $request): void
    {
        $result = $this->getAuthService()->resetPassword($request->getBody());
        JsonResponse::success($result, 200);
    }
}
