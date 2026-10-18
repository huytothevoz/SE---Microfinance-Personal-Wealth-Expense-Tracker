<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;
use WealthFlow\Core\ApiException;

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
        $includeArchived = (bool) $request->getQuery('include_archived', false);
        $wallets = $this->walletService->getWallets($userId, $includeArchived);
        JsonResponse::success(['wallets' => $wallets, 'count' => count($wallets)]);
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $wallet = $this->walletService->getWallet($id, $userId);
            JsonResponse::success(['wallet' => $wallet]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        }
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();

        try {
            $wallet = $this->walletService->createWallet($userId, $body);
            JsonResponse::success(['wallet' => $wallet, 'message' => 'Tạo ví mới thành công.'], 201);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Throwable $e) {
            JsonResponse::error('SERVER_ERROR', 'Không thể tạo ví lúc này. Vui lòng kiểm tra dữ liệu và liên hệ quản trị viên nếu lỗi tiếp diễn.', 500);
        }
    }

    public function update(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();

        try {
            $wallet = $this->walletService->updateWallet($id, $userId, $body);
            JsonResponse::success(['wallet' => $wallet, 'message' => 'Cập nhật ví thành công.']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function archive(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $this->walletService->archiveWallet($id, $userId);
            JsonResponse::success(['message' => 'Đã lưu trữ ví thành công.']);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('WALLET_ARCHIVE_REJECTED', $e->getMessage(), 422);
        }
    }

    public function delete(Request $request, int $id): void
    {
        // Deletion is intentionally a non-destructive archive operation.
        $this->archive($request, $id);
    }
}
