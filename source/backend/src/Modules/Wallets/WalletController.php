<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wallets;

use InvalidArgumentException;
use Throwable;
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

    public function history(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $history = $this->walletService->getWalletHistory($id, $userId);
            JsonResponse::success(['history' => $history, 'count' => count($history)]);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('NOT_FOUND', $e->getMessage(), 404);
        }
    }

    public function create(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();

        try {
            $wallet = $this->walletService->createWallet($userId, $body, $request->getIdempotencyKey());
            JsonResponse::success(['wallet' => $wallet, 'message' => 'Tạo ví mới thành công.'], 201);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log(sprintf('Wallet create failed [%s]: %s', $e::class, $e->getMessage()));
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
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (InvalidArgumentException $e) {
            JsonResponse::error('VALIDATION_ERROR', $e->getMessage(), 422);
        }
    }

    public function adjust(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $result = $this->walletService->adjustWallet(
            $id,
            $userId,
            $request->getBody(),
            $request->getIdempotencyKey()
        );
        JsonResponse::success($result, 201);
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
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            $this->walletService->deleteWallet($id, $userId);
            JsonResponse::success(['message' => 'Đã xóa vĩnh viễn ví chưa có lịch sử sử dụng.']);
        } catch (ApiException $e) {
            JsonResponse::error($e->errorCode, $e->getMessage(), $e->status, $e->fields);
        } catch (Throwable) {
            JsonResponse::error('SERVER_ERROR', 'Không thể xóa ví lúc này.', 500);
        }
    }
}
