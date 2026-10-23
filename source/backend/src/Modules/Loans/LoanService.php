<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Loans;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;
use WealthFlow\Core\ApiException;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Wallets\WalletDAO;

final class LoanService
{
    private LoanDAO $loanDao;
    private WalletDAO $walletDao;
    private LedgerDAO $ledgerDao;

    public function __construct(
        ?LoanDAO $loanDao = null,
        ?WalletDAO $walletDao = null,
        ?LedgerDAO $ledgerDao = null
    ) {
        $this->loanDao = $loanDao ?? new LoanDAO();
        $pdo = $this->loanDao->getPdo();
        $this->walletDao = $walletDao ?? new WalletDAO($pdo);
        $this->ledgerDao = $ledgerDao ?? new LedgerDAO($pdo);
    }

    /** @return array<int, array<string, mixed>> */
    public function listLoans(int $userId, ?string $direction = null, ?string $status = null): array
    {
        $normalizedDirection = $this->enum($direction, ['BORROWED', 'LENT'], 'Loại khoản vay');
        $normalizedStatus = $this->enum($status, ['ACTIVE', 'PAID_OFF', 'DEFAULTED'], 'Trạng thái khoản vay');
        return array_map(fn(array $loan): array => $this->present($loan), $this->loanDao->listByUser(
            $userId,
            $normalizedDirection,
            $normalizedStatus
        ));
    }

    /** @return array<string, mixed> */
    public function getLoan(int $id, int $userId): array
    {
        $loan = $this->loanDao->findByIdAndUser($id, $userId);
        if ($loan === null) {
            throw new ApiException('NOT_FOUND', 'Không tìm thấy khoản vay này.', 404);
        }
        $result = $this->present($loan);
        $result['schedules'] = array_map(static fn(array $row): array => [
            'id' => (int) $row['id'],
            'periodNumber' => (int) $row['period_number'],
            'dueDate' => (string) $row['due_date'],
            'principalDue' => (string) $row['principal_due'],
            'interestDue' => (string) $row['interest_due'],
            'principalPaid' => (string) $row['principal_paid'],
            'interestPaid' => (string) $row['interest_paid'],
            'status' => (string) $row['status'],
        ], $this->loanDao->listSchedules($id, $userId));
        $result['payments'] = array_map(static fn(array $payment): array => [
            'id' => (int) $payment['id'],
            'principalAmount' => (string) $payment['principal_amount'],
            'interestFeeAmount' => (string) $payment['interest_fee_amount'],
            'paidOn' => (string) $payment['paid_on'],
            'transactionId' => $payment['transaction_id'] === null ? null : (int) $payment['transaction_id'],
        ], $this->loanDao->listPayments($id, $userId));
        return $result;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function createLoan(int $userId, array $data, ?string $idempotencyKey): array
    {
        $title = $this->text($data['title'] ?? $data['name'] ?? '', 'Tên khoản vay', 2, 150);
        $partnerName = $this->text($data['partnerName'] ?? '', 'Tên đối tác', 2, 150);
        $rawDirection = strtoupper(trim((string) ($data['direction'] ?? $data['loanDirection'] ?? '')));
        $direction = match ($rawDirection) {
            'BORROWED', 'BORROWING' => 'BORROWED',
            'LENT', 'LENDING' => 'LENT',
            default => throw new InvalidArgumentException('Loại khoản vay không hợp lệ.'),
        };
        $principal = $this->decimal($data['principalAmount'] ?? $data['principal'] ?? null, 'Số tiền gốc', 8);
        $interestRate = $this->decimal($data['interestRateYear'] ?? 0, 'Lãi suất năm', 4, true);
        if (bccomp($interestRate, '100', 4) > 0) {
            throw new InvalidArgumentException('Lãi suất năm không được vượt quá 100%.');
        }
        $termMonths = filter_var($data['termMonths'] ?? null, FILTER_VALIDATE_INT);
        if ($termMonths === false || $termMonths < 1 || $termMonths > 600) {
            throw new InvalidArgumentException('Kỳ hạn phải từ 1 đến 600 tháng.');
        }
        $startDate = $this->date($data['startDate'] ?? date('Y-m-d'));
        $walletId = (int) ($data['walletId'] ?? 0);
        if ($walletId <= 0) throw new InvalidArgumentException('Hãy chọn ví nhận hoặc chi tiền.');
        $key = $this->idempotencyKey($idempotencyKey);
        $payload = compact('title', 'partnerName', 'direction', 'principal', 'interestRate', 'termMonths', 'startDate', 'walletId');

        return $this->atomicIdempotent($userId, $key, $payload, 201, function () use (
            $userId, $title, $partnerName, $direction, $principal, $interestRate, $termMonths, $startDate, $walletId
        ): array {
            $wallet = $this->requireWallet($walletId, $userId, true);
            $available = bcsub((string) $wallet['cached_balance'], (string) ($wallet['earmarked_balance'] ?? '0'), 8);
            if ($direction === 'LENT' && bccomp($available, $principal, 8) < 0) {
                throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư khả dụng không đủ để cho vay.', 422);
            }
            $firstDue = (new DateTimeImmutable($startDate))->modify('+1 month')->format('Y-m-d');
            $loanId = $this->loanDao->create([
                'userId' => $userId,
                'walletId' => $walletId,
                'title' => $title,
                'partnerName' => $partnerName,
                'direction' => $direction,
                'currency' => 'VND',
                'principal' => $principal,
                'interestRate' => $interestRate,
                'termMonths' => $termMonths,
                'startDate' => $startDate,
                'nextDueDate' => $firstDue,
            ]);
            $this->createSchedules($loanId, $principal, $interestRate, $termMonths, $startDate);

            $transactionId = $this->transaction(
                $userId,
                'LOAN_DISBURSEMENT',
                $direction === 'BORROWED' ? 'Nhận tiền vay: ' . $title : 'Giải ngân cho vay: ' . $title,
                $principal,
                $startDate
            );
            $walletAccount = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);
            $loanAccount = $this->loanDao->getOrCreateLoanAccount($userId, $loanId, $direction);
            if ($direction === 'BORROWED') {
                $this->entry($transactionId, $walletAccount, 'DEBIT', $principal);
                $this->entry($transactionId, $loanAccount, 'CREDIT', $principal);
                $newBalance = bcadd((string) $wallet['cached_balance'], $principal, 8);
            } else {
                $this->entry($transactionId, $loanAccount, 'DEBIT', $principal);
                $this->entry($transactionId, $walletAccount, 'CREDIT', $principal);
                $newBalance = bcsub((string) $wallet['cached_balance'], $principal, 8);
            }
            if (!$this->walletDao->setBalance($walletId, $userId, $newBalance)) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }
            return ['loan' => $this->getLoan($loanId, $userId), 'transactionId' => $transactionId];
        });
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function recordPayment(int $id, int $userId, array $data, ?string $idempotencyKey): array
    {
        $principal = $this->decimal($data['principalAmount'] ?? 0, 'Tiền gốc', 8, true);
        $interest = $this->decimal($data['interestFeeAmount'] ?? 0, 'Tiền lãi/phí', 8, true);
        if (bccomp(bcadd($principal, $interest, 8), '0', 8) <= 0) {
            throw new InvalidArgumentException('Khoản thanh toán phải có tiền gốc hoặc lãi/phí lớn hơn 0.');
        }
        $paidOn = $this->date($data['paidOn'] ?? date('Y-m-d'));
        $walletId = (int) ($data['walletId'] ?? 0);
        if ($walletId <= 0) throw new InvalidArgumentException('Hãy chọn ví thanh toán.');
        $key = $this->idempotencyKey($idempotencyKey);
        $payload = compact('id', 'principal', 'interest', 'paidOn', 'walletId');

        return $this->atomicIdempotent($userId, $key, $payload, 200, function () use (
            $id, $userId, $principal, $interest, $paidOn, $walletId
        ): array {
            $loan = $this->loanDao->findByIdAndUserForUpdate($id, $userId);
            if ($loan === null) throw new ApiException('NOT_FOUND', 'Không tìm thấy khoản vay này.', 404);
            if ((string) $loan['status'] !== 'ACTIVE') {
                throw new InvalidArgumentException('Khoản vay này không còn hoạt động.');
            }
            if (bccomp($principal, (string) $loan['principal_remaining'], 8) > 0) {
                throw new InvalidArgumentException('Tiền gốc thanh toán không được vượt quá dư gốc còn lại.');
            }
            $wallet = $this->requireWallet($walletId, $userId, true);
            $direction = (string) $loan['loan_direction'];
            $total = bcadd($principal, $interest, 8);
            if ($direction === 'BORROWED') {
                $available = bcsub((string) $wallet['cached_balance'], (string) ($wallet['earmarked_balance'] ?? '0'), 8);
                if (bccomp($available, $total, 8) < 0) {
                    throw new ApiException('INSUFFICIENT_FUNDS', 'Số dư khả dụng không đủ để trả khoản vay.', 422);
                }
            }

            $walletAccount = $this->walletDao->getOrCreateWalletLedgerAccountId($userId, $walletId);
            $loanAccount = $this->loanDao->getOrCreateLoanAccount($userId, $id, $direction);
            $primaryTransactionId = null;
            if (bccomp($principal, '0', 8) > 0) {
                $primaryTransactionId = $this->transaction(
                    $userId,
                    'LOAN_PAYMENT',
                    $direction === 'BORROWED' ? 'Trả gốc: ' . $loan['title'] : 'Thu hồi gốc: ' . $loan['title'],
                    $principal,
                    $paidOn
                );
                if ($direction === 'BORROWED') {
                    $this->entry($primaryTransactionId, $loanAccount, 'DEBIT', $principal);
                    $this->entry($primaryTransactionId, $walletAccount, 'CREDIT', $principal);
                } else {
                    $this->entry($primaryTransactionId, $walletAccount, 'DEBIT', $principal);
                    $this->entry($primaryTransactionId, $loanAccount, 'CREDIT', $principal);
                }
            }
            if (bccomp($interest, '0', 8) > 0) {
                $interestTransactionId = $this->transaction(
                    $userId,
                    $direction === 'BORROWED' ? 'EXPENSE' : 'INCOME',
                    ($direction === 'BORROWED' ? 'Trả lãi/phí: ' : 'Thu lãi: ') . $loan['title'],
                    $interest,
                    $paidOn
                );
                $interestAccount = $this->loanDao->getOrCreateAccount(
                    $userId,
                    $direction === 'BORROWED' ? 'EXPENSE_LOAN_INTEREST' : 'INCOME_LOAN_INTEREST',
                    $direction === 'BORROWED' ? 'EXPENSE' : 'INCOME'
                );
                if ($direction === 'BORROWED') {
                    $this->entry($interestTransactionId, $interestAccount, 'DEBIT', $interest);
                    $this->entry($interestTransactionId, $walletAccount, 'CREDIT', $interest);
                } else {
                    $this->entry($interestTransactionId, $walletAccount, 'DEBIT', $interest);
                    $this->entry($interestTransactionId, $interestAccount, 'CREDIT', $interest);
                }
                $primaryTransactionId ??= $interestTransactionId;
            }

            $newBalance = $direction === 'BORROWED'
                ? bcsub((string) $wallet['cached_balance'], $total, 8)
                : bcadd((string) $wallet['cached_balance'], $total, 8);
            if (!$this->walletDao->setBalance($walletId, $userId, $newBalance)) {
                throw new ApiException('WALLET_UPDATE_FAILED', 'Không thể cập nhật số dư ví.', 409);
            }
            $newRemaining = bcsub((string) $loan['principal_remaining'], $principal, 8);
            $this->loanDao->applyPaymentToSchedules($id, $principal, $interest);
            $status = bccomp($newRemaining, '0', 8) === 0 ? 'PAID_OFF' : 'ACTIVE';
            $nextDueDate = $status === 'ACTIVE' ? $this->loanDao->nextUnpaidDueDate($id) : null;
            if (!$this->loanDao->updateAfterPayment($id, $userId, $newRemaining, $status, $nextDueDate)) {
                throw new ApiException('LOAN_UPDATE_FAILED', 'Không thể cập nhật dư nợ.', 409);
            }
            $paymentId = $this->loanDao->addPayment($id, $primaryTransactionId, $principal, $interest, $paidOn);
            return [
                'loan' => $this->getLoan($id, $userId),
                'paymentId' => $paymentId,
                'transactionId' => $primaryTransactionId,
            ];
        });
    }

    /** @param callable(): array<string, mixed> $operation @return array<string, mixed> */
    private function atomicIdempotent(int $userId, string $key, array $payload, int $code, callable $operation): array
    {
        $pdo = $this->loanDao->getPdo();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) $pdo->beginTransaction();
        try {
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $record = $this->ledgerDao->findIdempotency($userId, $key);
            if ($record !== null) {
                if (!hash_equals((string) $record['request_hash'], $hash)) {
                    throw new ApiException('IDEMPOTENCY_CONFLICT', 'Khóa chống trùng đã được dùng cho yêu cầu khác.', 409);
                }
                $response = json_decode((string) $record['response_json'], true, 512, JSON_THROW_ON_ERROR);
                if ($ownsTransaction) $pdo->commit();
                return is_array($response) ? $response : [];
            }
            $response = $operation();
            $this->ledgerDao->saveIdempotency(
                $userId,
                $key,
                hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                isset($response['transactionId']) ? (int) $response['transactionId'] : null,
                $code,
                json_encode($response, JSON_THROW_ON_ERROR)
            );
            if ($ownsTransaction) $pdo->commit();
            return $response;
        } catch (Throwable $error) {
            if ($ownsTransaction && $pdo->inTransaction()) $pdo->rollBack();
            throw $error;
        }
    }

    private function createSchedules(int $loanId, string $principal, string $rate, int $term, string $startDate): void
    {
        $basePrincipal = bcdiv($principal, (string) $term, 8);
        $allocated = '0.00000000';
        $remaining = $principal;
        $start = new DateTimeImmutable($startDate);
        for ($period = 1; $period <= $term; $period++) {
            $principalDue = $period === $term ? bcsub($principal, $allocated, 8) : $basePrincipal;
            $interestDue = bcdiv(bcmul($remaining, $rate, 12), '1200', 8);
            $this->loanDao->addSchedule(
                $loanId,
                $period,
                $start->modify('+' . $period . ' month')->format('Y-m-d'),
                $principalDue,
                $interestDue
            );
            $allocated = bcadd($allocated, $principalDue, 8);
            $remaining = bcsub($remaining, $principalDue, 8);
        }
    }

    /** @return array<string, mixed> */
    private function requireWallet(int $walletId, int $userId, bool $forUpdate): array
    {
        $wallet = $this->walletDao->findByIdAndUser($walletId, $userId, $forUpdate);
        if ($wallet === null || (string) $wallet['status'] !== 'ACTIVE') {
            throw new ApiException('NOT_FOUND', 'Ví không tồn tại hoặc không thuộc quyền sở hữu.', 404);
        }
        if ((string) $wallet['wallet_type'] === 'CREDIT' || strtoupper((string) $wallet['currency']) !== 'VND') {
            throw new InvalidArgumentException('Khoản vay chỉ hỗ trợ ví tài sản VND đang hoạt động.');
        }
        return $wallet;
    }

    private function transaction(int $userId, string $type, string $description, string $amount, string $date): int
    {
        return $this->ledgerDao->createTransaction([
            'userId' => $userId,
            'categoryId' => null,
            'type' => $type,
            'status' => 'POSTED',
            'description' => $description,
            'currency' => 'VND',
            'amount' => $amount,
            'fxRateSnapshot' => '1.0000000000',
            'occurredOn' => $date,
            'postedAt' => date('Y-m-d H:i:s'),
            'originalTransactionId' => null,
        ]);
    }

    private function entry(int $transactionId, int $accountId, string $side, string $amount): void
    {
        $this->ledgerDao->createLedgerEntry([
            'transactionId' => $transactionId,
            'ledgerAccountId' => $accountId,
            'entrySide' => $side,
            'amountOriginal' => $amount,
            'currency' => 'VND',
            'amountBase' => $amount,
            'fxRateSnapshot' => '1.0000000000',
        ]);
    }

    private function enum(?string $value, array $allowed, string $label): ?string
    {
        if ($value === null || trim($value) === '') return null;
        $normalized = strtoupper(trim($value));
        if (!in_array($normalized, $allowed, true)) throw new InvalidArgumentException($label . ' không hợp lệ.');
        return $normalized;
    }

    private function text(mixed $value, string $label, int $min, int $max): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
        if (mb_strlen($text) < $min || mb_strlen($text) > $max) {
            throw new InvalidArgumentException($label . " cần từ {$min} đến {$max} ký tự.");
        }
        return $text;
    }

    private function decimal(mixed $value, string $label, int $scale, bool $allowZero = false): string
    {
        $raw = trim((string) $value);
        if (!preg_match('/^\d+(?:\.\d{1,' . $scale . '})?$/D', $raw)) {
            throw new InvalidArgumentException($label . ' không hợp lệ.');
        }
        $comparison = bccomp($raw, '0', $scale);
        if ($comparison < 0 || (!$allowZero && $comparison === 0)) {
            throw new InvalidArgumentException($label . ($allowZero ? ' không được âm.' : ' phải lớn hơn 0.'));
        }
        return bcadd($raw, '0', $scale);
    }

    private function date(mixed $value): string
    {
        $raw = trim((string) $value);
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
        if (!$date || $date->format('Y-m-d') !== $raw || $date > new DateTimeImmutable('today')) {
            throw new InvalidArgumentException('Ngày giao dịch không hợp lệ hoặc nằm trong tương lai.');
        }
        return $raw;
    }

    private function idempotencyKey(?string $key): string
    {
        $key = strtolower(trim((string) $key));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $key)) {
            throw new ApiException(
                $key === '' ? 'IDEMPOTENCY_KEY_REQUIRED' : 'INVALID_IDEMPOTENCY_KEY',
                $key === '' ? 'Ghi khoản vay cần Idempotency-Key.' : 'Idempotency-Key phải là UUID v4 hợp lệ.',
                400
            );
        }
        return $key;
    }

    /** @param array<string, mixed> $loan @return array<string, mixed> */
    private function present(array $loan): array
    {
        return [
            'id' => (int) $loan['id'],
            'name' => (string) $loan['title'],
            'partnerName' => (string) $loan['partner_name'],
            'direction' => (string) $loan['loan_direction'],
            'type' => (string) $loan['loan_direction'],
            'currency' => (string) $loan['currency'],
            'principalOriginal' => (string) $loan['principal_original'],
            'remainingPrincipal' => (string) $loan['principal_remaining'],
            'interestRateYear' => (string) $loan['interest_rate_year'],
            'termMonths' => (int) $loan['term_months'],
            'startDate' => (string) $loan['start_date'],
            'nextDueDate' => $loan['next_due_date'] === null ? null : (string) $loan['next_due_date'],
            'status' => (string) $loan['status'],
            'walletId' => $loan['wallet_id'] === null ? null : (int) $loan['wallet_id'],
            'walletName' => (string) ($loan['wallet_name'] ?? ''),
        ];
    }
}
