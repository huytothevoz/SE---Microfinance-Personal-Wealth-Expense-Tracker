<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Wealth;

use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\JsonResponse;
use WealthFlow\Core\Request;

final class GoldController
{
    public function __construct(private readonly ?GoldAssetService $service = null)
    {
    }

    public function list(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $includeLiquidated = filter_var($request->getQuery('includeLiquidated', false), FILTER_VALIDATE_BOOL);
        try {
            $assets = $this->goldService()->listAssets($userId, $includeLiquidated);
            JsonResponse::success(['gold' => $assets, 'count' => count($assets)]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể tải danh mục tài sản vàng.');
        }
    }

    public function show(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success(['asset' => $this->goldService()->getAsset($id, $userId)]);
        } catch (Throwable $error) {
            $this->error($error, 'Không thể tải tài sản vàng này.');
        }
    }

    public function transact(Request $request): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        $body = $request->getBody();
        if (strtoupper((string) ($body['actionType'] ?? 'BUY')) !== 'BUY') {
            JsonResponse::error('VALIDATION_ERROR', 'Endpoint tạo giao dịch hiện chỉ nhận thao tác BUY; dùng thanh lý cho SELL.', 422);
            return;
        }
        try {
            JsonResponse::success(
                $this->goldService()->buy($userId, $body, $request->getIdempotencyKey()),
                201
            );
        } catch (Throwable $error) {
            $this->error($error, 'Không thể ghi nhận giao dịch mua vàng.');
        }
    }

    public function revalue(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success($this->goldService()->revalue(
                $id,
                $userId,
                $request->getBody(),
                $request->getIdempotencyKey()
            ));
        } catch (Throwable $error) {
            $this->error($error, 'Không thể cập nhật định giá tài sản vàng.');
        }
    }

    public function liquidate(Request $request, int $id): void
    {
        $userId = AuthContext::requirePersonalUser(AuthContext::getUserId($request));
        try {
            JsonResponse::success($this->goldService()->liquidate(
                $id,
                $userId,
                $request->getBody(),
                $request->getIdempotencyKey()
            ));
        } catch (Throwable $error) {
            $this->error($error, 'Không thể thanh lý tài sản vàng.');
        }
    }

    private function goldService(): GoldAssetService
    {
        return $this->service ?? new GoldAssetService();
    }

    private function error(Throwable $error, string $fallback): void
    {
        if ($error instanceof ApiException) {
            JsonResponse::error($error->errorCode, $error->getMessage(), $error->status, $error->fields);
            return;
        }
        if ($error instanceof InvalidArgumentException) {
            JsonResponse::error('VALIDATION_ERROR', $error->getMessage(), 422);
            return;
        }
        error_log('Gold operation failed; type=' . get_class($error));
        JsonResponse::error('INTERNAL_ERROR', $fallback, 500);
    }
}
