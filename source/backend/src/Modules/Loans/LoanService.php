<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Loans;

use InvalidArgumentException;
use WealthFlow\Core\ApiException;

final class LoanService
{
    public function __construct(private readonly ?LoanDAO $dao = null)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function listLoans(int $userId, ?string $direction = null, ?string $status = null): array
    {
        $normalizedDirection = $this->enum($direction, ['BORROWED', 'LENT'], 'Loại khoản vay');
        $normalizedStatus = $this->enum($status, ['ACTIVE', 'PAID_OFF', 'DEFAULTED'], 'Trạng thái khoản vay');
        return array_map(fn(array $loan): array => $this->present($loan), $this->loanDao()->listByUser(
            $userId,
            $normalizedDirection,
            $normalizedStatus
        ));
    }

    /** @return array<string, mixed> */
    public function getLoan(int $id, int $userId): array
    {
        $loan = $this->loanDao()->findByIdAndUser($id, $userId);
        if ($loan === null) {
            throw new ApiException('NOT_FOUND', 'Không tìm thấy khoản vay này.', 404);
        }
        $result = $this->present($loan);
        $result['payments'] = array_map(static fn(array $payment): array => [
            'id' => (int) $payment['id'],
            'principalAmount' => (string) $payment['principal_amount'],
            'interestFeeAmount' => (string) $payment['interest_fee_amount'],
            'paidOn' => (string) $payment['paid_on'],
            'transactionId' => $payment['transaction_id'] === null ? null : (int) $payment['transaction_id'],
        ], $this->loanDao()->listPayments($id, $userId));
        return $result;
    }

    private function loanDao(): LoanDAO
    {
        return $this->dao ?? new LoanDAO();
    }

    private function enum(?string $value, array $allowed, string $label): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }
        $normalized = strtoupper(trim($value));
        if (!in_array($normalized, $allowed, true)) {
            throw new InvalidArgumentException($label . ' không hợp lệ.');
        }
        return $normalized;
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
