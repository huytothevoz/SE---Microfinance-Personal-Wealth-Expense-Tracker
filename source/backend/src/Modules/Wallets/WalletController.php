<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class WalletController
{
    private WalletService $walletService;

    public function __construct(?WalletService $walletService = null)
    {
        $this->walletService = $walletService ?? new WalletService();
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $status = (string) $request->getQuery('status', 'all');
        if ($request->getQuery('include_archived') !== null) {
            $status = filter_var($request->getQuery('include_archived'), FILTER_VALIDATE_BOOLEAN) ? 'all' : 'active';
        }
        JsonResponse::success($this->walletService->listWallets($userId, $status));
    }

    public function show(Request $request, string|int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        JsonResponse::success($this->walletService->getWallet($userId, $this->walletId($id)));
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        JsonResponse::success(
            $this->walletService->createWallet($userId, $request->getBody(), $request->getIdempotencyKey()),
            201
        );
    }

    public function update(Request $request, string|int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        JsonResponse::success(
            $this->walletService->updateWallet($userId, $this->walletId($id), $request->getBody())
        );
    }

    public function adjust(Request $request, string|int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        JsonResponse::success(
            $this->walletService->adjustWallet(
                $userId,
                $this->walletId($id),
                $request->getBody(),
                $request->getIdempotencyKey()
            ),
            201
        );
    }

    public function archive(Request $request, string|int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $this->walletService->archiveWallet($userId, $this->walletId($id));
        JsonResponse::success(['message' => 'Đã lưu trữ ví thành công.']);
    }

    private function walletId(string|int $value): int
    {
        $raw = (string) $value;
        if (!preg_match('/^[1-9]\d*$/D', $raw)) {
            throw new ApiException('INVALID_WALLET_ID', 'Mã ví không hợp lệ.', 422);
        }
        return (int) $raw;
    }
}
