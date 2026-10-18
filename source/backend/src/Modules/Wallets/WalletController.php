<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use WealthFlow\Http\JsonResponse;
use WealthFlow\Http\Request;
use WealthFlow\Modules\Auth\AuthContext;

final class WalletController
{
    public function __construct(private readonly ?WalletService $service = null)
    {
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $result = $this->walletService()->listWallets(
            $userId,
            (string) $request->getQuery('status', 'active')
        );
        JsonResponse::success($result);
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $result = $this->walletService()->createWallet(
            $userId,
            $request->getBody(),
            $request->getIdempotencyKey()
        );
        JsonResponse::success($result, 201);
    }

    public function update(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        JsonResponse::success($this->walletService()->updateWallet($userId, $id, $request->getBody()));
    }

    public function archive(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $this->walletService()->archiveWallet($userId, $id);
        JsonResponse::success(['message' => 'Đã lưu trữ ví.']);
    }

    public function adjust(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $result = $this->walletService()->adjustWallet(
            $userId,
            $id,
            $request->getBody(),
            $request->getIdempotencyKey()
        );
        JsonResponse::success($result, 201);
    }

    public function deletePermanently(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $this->walletService()->deleteWalletPermanently($userId, $id);
        JsonResponse::success(['message' => 'Đã xóa ví. Lịch sử tài chính được giữ nguyên.']);
    }

    private function walletService(): WalletService
    {
        return $this->service ?? new WalletService();
    }
}
