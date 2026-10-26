<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Goals;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;

final class GoalService
{
    private FinancialGoalDAO $goalDao;
    private WalletDAO $walletDao;
    private LedgerDAO $ledgerDao;

    public function __construct(
        ?FinancialGoalDAO $goalDao = null,
        ?WalletDAO $walletDao = null,
        ?LedgerDAO $ledgerDao = null
    )
    {
        $this->goalDao = $goalDao ?? new FinancialGoalDAO();
        $this->walletDao = $walletDao ?? new WalletDAO($this->goalDao->getPdo());
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO($this->goalDao->getPdo());
    }

    /** @return array<int, array<string, mixed>> */
    public function listGoals(int $userId, ?string $status = null): array
    {
        $normalizedStatus = $status !== null && trim($status) !== '' ? strtoupper(trim($status)) : null;
        if ($normalizedStatus !== null && !in_array($normalizedStatus, ['ACTIVE', 'COMPLETED', 'CANCELLED'], true)) {
            throw new InvalidArgumentException('Trạng thái mục tiêu không hợp lệ.');
        }
        return array_map(fn(array $goal): array => $this->present($goal), $this->goalDao->listByUser($userId, $normalizedStatus));
    }

    /** @return array<string, mixed> */
    public function getGoal(int $id, int $userId): array
    {
        $goal = $this->requireGoal($id, $userId);
        $result = $this->present($goal);
        $result['allocations'] = array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'type' => (string) $row['allocation_type'],
            'amount' => (string) $row['amount'],
            'allocatedOn' => (string) $row['allocated_on'],
            'note' => (string) ($row['note'] ?? ''),
            'walletId' => $row['wallet_id'] !== null ? (int) $row['wallet_id'] : null,
            'walletName' => (string) ($row['wallet_name'] ?? 'Ví đã xóa'),
        ], $this->goalDao->listAllocations($id, $userId));
        return $result;
    }

    /** @param array<string, mixed> $data */
    public function createGoal(int $userId, array $data): array
    {
        $name = $this->name($data['name'] ?? '');
        $target = $this->amount($data['targetAmount'] ?? null, 'Số tiền muốn đạt');
        $deadline = $this->deadline($data['deadline'] ?? null);
        $priority = $this->priority($data['priority'] ?? 'MEDIUM');

        $id = $this->goalDao->create($userId, null, $name, $target, $deadline, $priority);
        return $this->getGoal($id, $userId);
    }

    /** @param array<string, mixed> $data */
    public function updateGoal(int $id, int $userId, array $data): array
    {
        $goal = $this->requireGoal($id, $userId);
        if ((string) $goal['status'] === 'CANCELLED') {
            throw new InvalidArgumentException('Mục tiêu đã hủy nên không thể chỉnh sửa.');
        }
        $name = $this->name($data['name'] ?? $goal['name']);
        $target = $this->amount($data['targetAmount'] ?? $goal['target_amount'], 'Số tiền muốn đạt');
        $deadline = $this->deadline($data['deadline'] ?? $goal['deadline'], true);
        $priority = $this->priority($data['priority'] ?? $goal['priority']);
        $status = bccomp((string) $goal['current_amount'], $target, 8) >= 0 ? 'COMPLETED' : 'ACTIVE';
        $this->goalDao->updateInfo($id, $userId, $name, $target, $deadline, $priority, $status);
        return $this->getGoal($id, $userId);
    }

    /** @param array<string, mixed> $data */
    public function allocate(
        int $id,
        int $userId,
        string $type,
        array $data,
        ?string $idempotencyKey = null
    ): array
    {
        $type = strtoupper($type);
        if (!in_array($type, ['DEPOSIT', 'WITHDRAWAL'], true)) {
            throw new InvalidArgumentException('Loại thay đổi số tiền không hợp lệ.');
        }
        $amount = $this->amount($data['amount'] ?? null, 'Số tiền');
        $allocatedOn = $this->allocationDate($data['allocatedOn'] ?? date('Y-m-d'));
        $note = trim((string) ($data['note'] ?? ''));
        if (mb_strlen($note) > 250) {
            throw new InvalidArgumentException('Ghi chú không được dài quá 250 ký tự.');
        }
        $key = $this->idempotencyKey($idempotencyKey);
        $walletId = (int) ($data['walletId'] ?? 0);
        $payload = [
            'goalId' => $id,
            'type' => $type,
            'walletId' => $walletId,
            'amount' => $amount,
            'allocatedOn' => $allocatedOn,
            'note' => $note,
        ];
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        $pdo = $this->goalDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();
        try {
            $record = $this->ledgerDao->findIdempotency($userId, $key);
            if ($record !== null) {
                if (!hash_equals((string) $record['request_hash'], $hash)) {
                    throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa chống trùng đã được dùng cho yêu cầu khác.', 409);
                }
                $cached = json_decode((string) $record['response_json'], true, 512, JSON_THROW_ON_ERROR);
                if ($ownsTransaction) $pdo->commit();
                return is_array($cached) ? $cached : [];
            }
            $goal = $this->goalDao->findByIdAndUser($id, $userId, true);
            if ($goal === null) throw new ApiException('NOT_FOUND', 'Không tìm thấy mục tiêu này.', 404);
            if ((string) $goal['status'] === 'CANCELLED') {
                throw new InvalidArgumentException('Mục tiêu đã hủy nên không thể thay đổi số tiền.');
            }
            if ($type === 'DEPOSIT' && (string) $goal['status'] !== 'ACTIVE') {
                throw new InvalidArgumentException('Mục tiêu này đã hoàn thành. Hãy chỉnh lại kế hoạch nếu muốn tiếp tục để dành.');
            }
            $this->requireVndWallet($walletId, $userId);
            $current = (string) $goal['current_amount'];

            if ($type === 'DEPOSIT') {
                if (!$this->goalDao->increaseWalletEarmark($walletId, $userId, $amount)) {
                    throw new InvalidArgumentException('Ví không đủ tiền chưa phân bổ cho khoản để dành này.');
                }
                $newAmount = bcadd($current, $amount, 8);
            } else {
                if (bccomp($current, $amount, 8) < 0) {
                    throw new InvalidArgumentException('Số tiền lấy ra không được lớn hơn số đã để dành.');
                }
                $walletAllocation = $this->goalDao->allocatedAmountForWallet($id, $walletId, $userId);
                if (bccomp($walletAllocation, $amount, 8) < 0) {
                    throw new InvalidArgumentException('Ví đã chọn không có đủ tiền đang để dành trong mục tiêu này.');
                }
                if (!$this->goalDao->decreaseWalletEarmark($walletId, $userId, $amount)) {
                    throw new InvalidArgumentException('Số tiền đang phân bổ trong ví không còn khớp. Vui lòng kiểm tra lại.');
                }
                $newAmount = bcsub($current, $amount, 8);
            }

            $status = bccomp($newAmount, (string) $goal['target_amount'], 8) >= 0 ? 'COMPLETED' : 'ACTIVE';
            $this->goalDao->addAllocation($id, $walletId, $type, $amount, $allocatedOn, $note !== '' ? $note : null);
            $this->goalDao->updateAmountAndStatus($id, $userId, $newAmount, $status);
            $response = $this->getGoal($id, $userId);
            $this->ledgerDao->saveIdempotency(
                $userId,
                $key,
                $hash,
                null,
                200,
                json_encode($response, JSON_THROW_ON_ERROR)
            );
            if ($ownsTransaction) $pdo->commit();
            return $response;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    public function completeGoal(int $id, int $userId): array
    {
        $goal = $this->requireGoal($id, $userId);
        if ((string) $goal['status'] === 'CANCELLED') {
            throw new InvalidArgumentException('Mục tiêu đã hủy.');
        }
        if (bccomp((string) $goal['current_amount'], (string) $goal['target_amount'], 8) < 0) {
            throw new InvalidArgumentException('Bạn chưa để dành đủ số tiền của mục tiêu này.');
        }
        $this->goalDao->setStatus($id, $userId, 'COMPLETED');
        return $this->getGoal($id, $userId);
    }

    public function cancelGoal(int $id, int $userId): void
    {
        $pdo = $this->goalDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();
        try {
            $goal = $this->goalDao->findByIdAndUser($id, $userId, true);
            if ($goal === null) throw new ApiException('NOT_FOUND', 'Không tìm thấy mục tiêu này.', 404);
            if ((string) $goal['status'] === 'CANCELLED') {
                throw new InvalidArgumentException('Mục tiêu này đã được hủy trước đó.');
            }
            foreach ($this->goalDao->allocatedWalletBalances($id, $userId) as $walletBalance) {
                $walletId = (int) $walletBalance['wallet_id'];
                $amount = (string) $walletBalance['amount'];
                if (!$this->goalDao->decreaseWalletEarmark($walletId, $userId, $amount)) {
                    throw new InvalidArgumentException('Không thể hoàn lại số tiền đã để dành về ví. Vui lòng kiểm tra lại.');
                }
                $this->goalDao->addAllocation(
                    $id,
                    $walletId,
                    'WITHDRAWAL',
                    $amount,
                    date('Y-m-d'),
                    'Hoàn lại khi dừng mục tiêu'
                );
            }
            $this->goalDao->updateAmountAndStatus($id, $userId, '0.00000000', 'CANCELLED');
            if ($ownsTransaction) $pdo->commit();
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    /** @return array<string, mixed> */
    private function requireGoal(int $id, int $userId): array
    {
        $goal = $this->goalDao->findByIdAndUser($id, $userId);
        if ($goal === null) throw new ApiException('NOT_FOUND', 'Không tìm thấy mục tiêu này.', 404);
        return $goal;
    }

    /** @return array<string, mixed> */
    private function requireVndWallet(int $walletId, int $userId): array
    {
        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId);
        if ($wallet === null || (string) $wallet['status'] !== 'ACTIVE') {
            throw new InvalidArgumentException('Hãy chọn một ví đang sử dụng.');
        }
        if (strtoupper((string) $wallet['currency']) !== 'VND') {
            throw new InvalidArgumentException('Mục tiêu tiết kiệm hiện dùng ví VND để số tiền luôn rõ ràng.');
        }
        if ((string) $wallet['wallet_type'] === 'CREDIT') {
            throw new InvalidArgumentException('Mục tiêu tiết kiệm chỉ được liên kết với ví tài sản, không dùng thẻ tín dụng.');
        }
        return $wallet;
    }

    private function name(mixed $value): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Tên mục tiêu cần từ 2 đến 150 ký tự.');
        }
        return $name;
    }

    private function amount(mixed $value, string $label): string
    {
        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            throw new InvalidArgumentException($label . ' không hợp lệ.');
        }
        $raw = trim((string) $value);
        if (!preg_match('/^\d+(?:\.\d{1,8})?$/D', $raw) || bccomp($raw, '0', 8) <= 0) {
            throw new InvalidArgumentException($label . ' phải lớn hơn 0.');
        }
        return bcadd($raw, '0', 8);
    }

    private function deadline(mixed $value, bool $allowToday = false): string
    {
        $raw = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date || $date->format('Y-m-d') !== $raw) {
            throw new InvalidArgumentException('Ngày dự kiến hoàn thành không hợp lệ.');
        }
        $today = new DateTimeImmutable('today');
        if ($date < $today || (!$allowToday && $date == $today)) {
            throw new InvalidArgumentException('Ngày dự kiến hoàn thành phải ở tương lai.');
        }
        return $raw;
    }

    private function allocationDate(mixed $value): string
    {
        $raw = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date || $date->format('Y-m-d') !== $raw || $date > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Ngày để dành không hợp lệ hoặc nằm trong tương lai.');
        }
        return $raw;
    }

    private function priority(mixed $value): string
    {
        $priority = strtoupper(trim((string) $value));
        if (!in_array($priority, ['LOW', 'MEDIUM', 'HIGH'], true)) {
            throw new InvalidArgumentException('Mức ưu tiên không hợp lệ.');
        }
        return $priority;
    }

    private function idempotencyKey(?string $key): string
    {
        $key = strtolower(trim((string) $key));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $key)) {
            throw new ApiException(
                $key === '' ? 'IDEMPOTENCY_KEY_REQUIRED' : 'INVALID_IDEMPOTENCY_KEY',
                $key === '' ? 'Phân bổ mục tiêu cần Idempotency-Key.' : 'Idempotency-Key phải là UUID v4 hợp lệ.',
                400
            );
        }
        return $key;
    }

    /** @param array<string, mixed> $goal @return array<string, mixed> */
    private function present(array $goal): array
    {
        $target = (string) $goal['target_amount'];
        $current = (string) $goal['current_amount'];
        $remaining = bccomp($current, $target, 8) >= 0 ? '0.00000000' : bcsub($target, $current, 8);
        $today = new DateTimeImmutable('today');
        $deadline = new DateTimeImmutable((string) $goal['deadline']);
        $days = max(0, (int) $today->diff($deadline)->format('%r%a'));
        $months = max(1, (int) ceil($days / 30.4375));
        return [
            'id' => (int) $goal['id'],
            'name' => (string) $goal['name'],
            'targetAmount' => $target,
            'currentAmount' => $current,
            'remainingAmount' => $remaining,
            'progressPercent' => bccomp($target, '0', 8) > 0
                ? (bccomp($current, $target, 8) >= 0 ? '100.0' : bcmul(bcdiv($current, $target, 8), '100', 1))
                : '0.0',
            'monthlySavingTarget' => bccomp($remaining, '0', 8) > 0
                ? bcdiv($remaining, (string) $months, 2)
                : '0.00',
            'deadline' => (string) $goal['deadline'],
            'priority' => (string) $goal['priority'],
            'status' => (string) $goal['status'],
            'walletId' => $goal['wallet_id'] !== null ? (int) $goal['wallet_id'] : null,
            'walletName' => (string) ($goal['wallet_name'] ?? ''),
            'currency' => (string) ($goal['wallet_currency'] ?? 'VND'),
        ];
    }
}
