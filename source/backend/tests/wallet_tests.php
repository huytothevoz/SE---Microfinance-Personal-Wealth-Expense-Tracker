<?php

declare(strict_types=1);

use WealthFlow\Core\ApiException;
use WealthFlow\Core\AuthContext;
use WealthFlow\Core\Database;
use WealthFlow\Modules\Transactions\ExchangeRateService;
use WealthFlow\Modules\Transactions\LedgerDAO;
use WealthFlow\Modules\Transactions\VietcombankRateProvider;
use WealthFlow\Modules\Wallets\WalletDAO;
use WealthFlow\Modules\Wallets\WalletService;

spl_autoload_register(static function (string $class): void {
    $prefix = 'WealthFlow\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

final class WalletTestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): int
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $this->createSchema($pdo);
        Database::setConnection($pdo, 'sqlite');

        $walletDao = new WalletDAO($pdo);
        $ledgerDao = new LedgerDAO($pdo);
        $rates = new ExchangeRateService($pdo);
        $service = new WalletService($walletDao, $ledgerDao, $rates);

        echo "Wallet & Vietcombank feature tests\n";

        $createKey = '11111111-1111-4111-8111-111111111111';
        $created = $service->createWallet(1, [
            'name' => 'Ví tiền mặt kiểm thử',
            'type' => 'CASH',
            'currency' => 'VND',
            'openingBalance' => '2000000',
        ], $createKey);
        $walletId = (int) $created['wallet']['id'];
        $txId = (int) $created['openingTransactionId'];
        $this->assert('create wallet with opening balance', $created['wallet']['balance'] == 2000000);

        $transaction = $ledgerDao->findByIdAndUser($txId, 1);
        $this->assert('opening balance uses OTHER, not INCOME', $transaction['transaction_type'] === 'OTHER');
        $this->assert('opening transaction has two entries', count($transaction['entries']) === 2);
        $this->assert('opening entries are balanced', $this->balanced($transaction['entries']));

        $replayed = $service->createWallet(1, [
            'name' => 'Ví tiền mặt kiểm thử',
            'type' => 'CASH',
            'currency' => 'VND',
            'openingBalance' => '2000000',
        ], $createKey);
        $walletCount = (int) $pdo->query("SELECT COUNT(*) FROM wallets WHERE name = 'Ví tiền mặt kiểm thử'")->fetchColumn();
        $this->assert('create idempotency replays original response', $replayed['wallet']['id'] === $walletId && $walletCount === 1);

        $this->expectApi('idempotency conflict is rejected', 'IDEMPOTENCY_CONFLICT', static function () use ($service, $createKey): void {
            $service->createWallet(1, [
                'name' => 'Ví khác', 'type' => 'CASH', 'currency' => 'VND', 'openingBalance' => '0',
            ], $createKey);
        });
        $this->expectApi('duplicate wallet name is rejected', 'DUPLICATE_WALLET_NAME', static function () use ($service): void {
            $service->createWallet(1, [
                'name' => 'Ví tiền mặt kiểm thử', 'type' => 'BANK', 'currency' => 'VND', 'openingBalance' => '0',
            ], '22222222-2222-4222-8222-222222222222');
        });

        $foreign = $service->createWallet(1, [
            'name' => 'Ví USD', 'type' => 'BANK', 'currency' => 'USD', 'openingBalance' => '100.00',
        ], '33333333-3333-4333-8333-333333333333');
        $foreignTx = $ledgerDao->findByIdAndUser((int) $foreign['openingTransactionId'], 1);
        $this->assert('foreign opening balance snapshots USD/VND rate', (float) $foreignTx['fx_rate_snapshot'] === 25400.0);
        $this->assert('foreign opening entry converts to base currency', (float) $foreignTx['entries'][0]['amount_base'] === 2540000.0);

        $typeWallets = [];
        foreach (['BANK', 'E_WALLET', 'SAVINGS', 'CREDIT'] as $index => $type) {
            $typeWallets[$type] = $service->createWallet(1, [
                'name' => 'Loại ví ' . $type,
                'type' => $type,
                'currency' => 'VND',
                'openingBalance' => '0',
            ], sprintf('40000000-0000-4000-8000-%012d', $index + 1));
        }
        $this->assert('all five database wallet types are supported',
            (int) $pdo->query('SELECT COUNT(DISTINCT wallet_type) FROM wallets WHERE user_id = 1')->fetchColumn() === 5);

        $creditId = (int) $typeWallets['CREDIT']['wallet']['id'];
        $creditAdjusted = $service->adjustWallet(1, $creditId, [
            'direction' => 'DECREASE', 'amount' => '750000', 'note' => 'Ghi nhận dư nợ thẻ',
        ], '49999999-9999-4999-8999-999999999999');
        $this->assert('credit wallet can carry a negative liability balance',
            $creditAdjusted['wallet']['balance'] == -750000);

        $updated = $service->updateWallet(1, $walletId, ['name' => 'Ví tiền mặt đã đổi tên']);
        $this->assert('wallet name can be updated', $updated['wallet']['name'] === 'Ví tiền mặt đã đổi tên');
        $this->expectApi('another user cannot update wallet', 'NOT_FOUND', static function () use ($service, $walletId): void {
            $service->updateWallet(2, $walletId, ['name' => 'Không được phép']);
        });

        $adjustKey = '55555555-5555-4555-8555-555555555555';
        $adjusted = $service->adjustWallet(1, $walletId, [
            'direction' => 'INCREASE', 'amount' => '500000', 'note' => 'Đối soát tăng',
        ], $adjustKey);
        $adjustTx = $ledgerDao->findByIdAndUser((int) $adjusted['transactionId'], 1);
        $this->assert('increase adjustment updates cached balance', $adjusted['wallet']['balance'] == 2500000);
        $this->assert('adjustment creates balanced ledger entries', $this->balanced($adjustTx['entries']));
        $txCountBeforeReplay = (int) $pdo->query('SELECT COUNT(*) FROM financial_transactions')->fetchColumn();
        $adjustReplay = $service->adjustWallet(1, $walletId, [
            'direction' => 'INCREASE', 'amount' => '500000', 'note' => 'Đối soát tăng',
        ], $adjustKey);
        $this->assert('adjustment idempotency prevents duplicate posting',
            $adjustReplay['transactionId'] === $adjusted['transactionId']
            && (int) $pdo->query('SELECT COUNT(*) FROM financial_transactions')->fetchColumn() === $txCountBeforeReplay);

        $balanceBeforeFailure = (string) $walletDao->findByIdAndUser($walletId, 1)['cached_balance'];
        $this->expectApi('decrease beyond balance is rejected', 'INSUFFICIENT_FUNDS', static function () use ($service, $walletId): void {
            $service->adjustWallet(1, $walletId, [
                'direction' => 'DECREASE', 'amount' => '999999999', 'note' => '',
            ], '66666666-6666-4666-8666-666666666666');
        });
        $this->assert('failed decrease rolls back balance',
            (string) $walletDao->findByIdAndUser($walletId, 1)['cached_balance'] === $balanceBeforeFailure);
        $this->expectApi('non-zero wallet cannot be archived', 'WALLET_BALANCE_NOT_ZERO',
            static fn () => $service->archiveWallet(1, $walletId));

        $zero = $service->createWallet(1, [
            'name' => 'Ví rỗng', 'type' => 'E_WALLET', 'currency' => 'VND', 'openingBalance' => '0',
        ], '77777777-7777-4777-8777-777777777777');
        $zeroId = (int) $zero['wallet']['id'];
        $service->archiveWallet(1, $zeroId);
        $this->assert('zero-balance wallet can be archived', $walletDao->findByIdAndUser($zeroId, 1)['status'] === 'ARCHIVED');

        $pdo->prepare("INSERT INTO wallets
            (user_id, name, wallet_type, currency, cached_balance, status)
            VALUES (?, 'Ví đã xóa từ phiên bản cũ', 'CASH', 'VND', 0, 'DELETED')")
            ->execute([1]);
        $deletedId = (int) $pdo->lastInsertId();
        $withoutDeleted = $service->listWallets(1, 'ALL');
        $this->assert('legacy deleted wallet is hidden from the refactor list',
            !in_array($deletedId, array_column($withoutDeleted['wallets'], 'id'), true)
            && $walletDao->findByIdAndUser($deletedId, 1) === null);

        $recurring = $service->createWallet(1, [
            'name' => 'Ví định kỳ', 'type' => 'BANK', 'currency' => 'VND', 'openingBalance' => '0',
        ], '88888888-8888-4888-8888-888888888888');
        $recurringId = (int) $recurring['wallet']['id'];
        $pdo->prepare("INSERT INTO recurring_schedules
            (user_id, wallet_id, name, amount, currency, frequency, day_of_month, next_due_date, execution_mode, status)
            VALUES (?, ?, 'Lịch kiểm thử', 1000, 'VND', 'MONTHLY', 1, '2026-11-01', 'DRAFT_REMINDER', 'ACTIVE')")
            ->execute([1, $recurringId]);
        $this->expectApi('active recurring schedule blocks archive', 'WALLET_HAS_ACTIVE_RECURRING',
            static fn () => $service->archiveWallet(1, $recurringId));

        $earmarked = $service->createWallet(1, [
            'name' => 'Ví earmark', 'type' => 'SAVINGS', 'currency' => 'VND', 'openingBalance' => '0',
        ], '99999999-9999-4999-8999-999999999999');
        $earmarkedId = (int) $earmarked['wallet']['id'];
        $pdo->exec("UPDATE wallets SET earmarked_balance = 1000 WHERE id = {$earmarkedId}");
        $this->expectApi('earmarked balance blocks archive', 'WALLET_EARMARK_NOT_ZERO',
            static fn () => $service->archiveWallet(1, $earmarkedId));

        $list = $service->listWallets(1, 'ALL');
        $this->assert('wallet summary counts active and archived wallets',
            $list['summary']['activeCount'] > 0 && $list['summary']['archivedCount'] === 1);
        $this->assert('wallet summary converts balances to user base currency',
            !$list['summary']['hasUnconvertedBalances'] && (float) $list['summary']['totalBalanceBase'] > 0);

        $this->expectApi('admin is blocked from personal-wallet authorization', 'PERSONAL_ACCOUNT_REQUIRED',
            static fn () => AuthContext::requirePersonalUser(3));

        $fakeProvider = new class extends VietcombankRateProvider {
            public function fetch(): array
            {
                return [
                    'provider' => 'VIETCOMBANK_TRANSFER',
                    'effectiveAt' => '2026-10-17 10:00:00',
                    'sourceUrl' => self::SOURCE_URL,
                    'rates' => ['USD' => '26000', 'EUR' => '28000'],
                ];
            }
        };
        $rates->syncPublicRates($fakeProvider);
        $this->assert('Vietcombank sync stores all direct, inverse and cross pairs',
            (int) $pdo->query("SELECT COUNT(*) FROM exchange_rates WHERE provider = 'VIETCOMBANK_TRANSFER' AND effective_at = '2026-10-17 10:00:00'")->fetchColumn() === 6);
        $this->assert('latest exchange rate uses synchronized Vietcombank snapshot',
            (float) $rates->getSnapshotRate('USD', 'VND') === 26000.0);
        $countBeforeRateReplay = (int) $pdo->query("SELECT COUNT(*) FROM exchange_rates WHERE provider = 'VIETCOMBANK_TRANSFER'")->fetchColumn();
        $rates->syncPublicRates($fakeProvider);
        $this->assert('same Vietcombank snapshot is not duplicated',
            (int) $pdo->query("SELECT COUNT(*) FROM exchange_rates WHERE provider = 'VIETCOMBANK_TRANSFER'")->fetchColumn() === $countBeforeRateReplay);

        $sourceRoot = dirname(__DIR__, 2);
        $appHtml = file_get_contents($sourceRoot . '/app.html');
        $appEntry = file_get_contents($sourceRoot . '/frontend/js/app.js');
        $walletUi = file_get_contents($sourceRoot . '/frontend/js/app/wallets.js');
        $uiCore = file_get_contents($sourceRoot . '/frontend/js/app/ui-core.js');
        $appCss = file_get_contents($sourceRoot . '/frontend/css/app.css');
        $walletView = file_get_contents($sourceRoot . '/frontend/views/wallets.html');
        $walletApi = file_get_contents($sourceRoot . '/frontend/js/services/wallet-api.js');
        $transactionUi = file_get_contents($sourceRoot . '/frontend/js/app/transactions.js');
        $transactionApi = file_get_contents($sourceRoot . '/frontend/js/services/transaction-api.js');
        $transactionView = file_get_contents($sourceRoot . '/frontend/views/transactions.html');
        $categoryView = file_get_contents($sourceRoot . '/frontend/views/categories.html');
        $mainUi = file_get_contents($sourceRoot . '/frontend/js/app/main.js');
        $runner = file_get_contents(dirname($sourceRoot) . '/run_wealthflow.sh');
        $this->assert('application keeps the new-refactor wrapper entry structure',
            is_string($appHtml)
            && str_contains($appHtml, 'frontend/js/app.js')
            && is_string($appEntry)
            && str_contains($appEntry, "import('./app/main.js"));
        preg_match('/wallet-refactor-\d{8}-\d+/', (string) $appHtml, $cacheVersion);
        $this->assert('frontend cache version is consistent across entry and runner checks',
            isset($cacheVersion[0])
            && str_contains((string) $appEntry, $cacheVersion[0])
            && str_contains((string) $runner, $cacheVersion[0]));
        $this->assert('wallet frontend contains no fixture or hard-coded demo data',
            is_string($walletUi)
            && !str_contains($walletUi, 'data/fixtures')
            && is_string($walletView)
            && !str_contains($walletView, '91.550.000')
            && !str_contains($walletView, '26.000 VND'));
        $this->assert('every wallet type has a valid icon and its own visual color',
            is_string($walletUi)
            && str_contains($walletUi, "CASH: { icon: 'cash', tone: 'cash' }")
            && str_contains($walletUi, "BANK: { icon: 'bank', tone: 'bank' }")
            && str_contains($walletUi, "E_WALLET: { icon: 'wallet', tone: 'ewallet' }")
            && str_contains($walletUi, "SAVINGS: { icon: 'savings', tone: 'savings' }")
            && str_contains($walletUi, "CREDIT: { icon: 'card', tone: 'credit' }")
            && is_string($uiCore)
            && str_contains($uiCore, "cash: '")
            && str_contains($uiCore, "bank: '")
            && str_contains($uiCore, "savings: '")
            && str_contains($uiCore, "card: '")
            && is_string($appCss)
            && str_contains($appCss, '.wallet-card--cash')
            && str_contains($appCss, '.wallet-card--bank')
            && str_contains($appCss, '.wallet-card--ewallet')
            && str_contains($appCss, '.wallet-card--savings')
            && str_contains($appCss, '.wallet-card--credit'));
        $this->assert('wallet card groups rename, balance adjustment and archive under one edit action',
            is_string($walletUi)
            && str_contains($walletUi, 'data-action="wallet-manage"')
            && str_contains($walletUi, 'wallet-edit-menu')
            && str_contains($walletUi, 'data-action="wallet-edit"')
            && str_contains($walletUi, 'data-action="wallet-adjust"')
            && str_contains($walletUi, 'data-action="wallet-archive"'));
        $this->assert('wallet API uses the implemented routes instead of the obsolete demo route',
            is_string($walletApi)
            && str_contains($walletApi, 'exchange-rates/sync')
            && !str_contains($walletApi, 'exchange-rates/refresh'));
        $this->assert('transaction and category frontend use APIs instead of fixtures',
            is_string($transactionUi)
            && !str_contains($transactionUi, 'data/fixtures')
            && str_contains($transactionUi, 'TransactionApi.getTransactions')
            && str_contains($transactionUi, 'TransactionApi.createTransaction')
            && str_contains($transactionUi, 'TransactionApi.archiveCategory')
            && is_string($transactionApi)
            && str_contains($transactionApi, "httpClient('transactions'")
            && str_contains($transactionApi, "httpClient('categories'"));
        $this->assert('transaction and category views expose real render targets and filters',
            is_string($transactionView)
            && str_contains($transactionView, 'id="transaction-table"')
            && is_string($categoryView)
            && str_contains($categoryView, 'id="category-card-list"')
            && str_contains($categoryView, 'id="default-category-card-list"')
            && str_contains($categoryView, 'data-category-filter="income"')
            && str_contains($categoryView, 'data-category-filter="expense"'));
        $this->assert('SPA navigation renders a visible error instead of a blank page',
            is_string($mainUi)
            && str_contains($mainUi, 'Không thể tải trang')
            && str_contains($mainUi, "console.error(`Không thể hiển thị trang"));
        $this->assert('runner verifies frontend entry and required views before reporting ready',
            is_string($runner)
            && str_contains($runner, 'frontend_healthy()')
            && str_contains($runner, '/frontend/views/overview.html')
            && str_contains($runner, '/frontend/views/wallets.html')
            && str_contains($runner, '/frontend/views/transactions.html')
            && str_contains($runner, '/frontend/views/categories.html'));
        $this->assert('runner resets XAMPP services and binds WealthFlow only to port 8080',
            is_string($runner)
            && str_contains($runner, 'WEB_PORT="8080"')
            && !str_contains($runner, 'choose_web_port')
            && str_contains($runner, '"$XAMPP_DIR/lampp" stop')
            && str_contains($runner, '"$XAMPP_DIR/lampp" startmysql')
            && str_contains($runner, 'verify_database')
            && str_contains($runner, 'prepare_clean_runtime')
            && str_contains($runner, 'DROP DATABASE IF EXISTS')
            && str_contains($runner, 'CREATE DATABASE')
            && str_contains($runner, '"$PHP_BIN" "$MIGRATOR_PHP" --no-config'));

        echo "\nPassed: {$this->passed}; Failed: {$this->failed}\n";
        return $this->failed === 0 ? 0 : 1;
    }

    /** @param array<int, array<string, mixed>> $entries */
    private function balanced(array $entries): bool
    {
        $debit = '0';
        $credit = '0';
        foreach ($entries as $entry) {
            if ($entry['entry_side'] === 'DEBIT') {
                $debit = bcadd($debit, (string) $entry['amount_base'], 8);
            } else {
                $credit = bcadd($credit, (string) $entry['amount_base'], 8);
            }
        }
        return bccomp($debit, $credit, 8) === 0;
    }

    private function assert(string $name, bool $condition): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$name}\n";
        } else {
            $this->failed++;
            echo "  [FAIL] {$name}\n";
        }
    }

    private function expectApi(string $name, string $code, callable $action): void
    {
        try {
            $action();
            $this->assert($name, false);
        } catch (ApiException $error) {
            $this->assert($name, $error->errorCode === $code);
        }
    }

    private function createSchema(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE users (
  id INTEGER PRIMARY KEY AUTOINCREMENT, full_name TEXT NOT NULL, email TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL, role TEXT NOT NULL DEFAULT 'USER', status TEXT NOT NULL DEFAULT 'ACTIVE',
  base_currency TEXT NOT NULL DEFAULT 'VND', created_at TEXT DEFAULT CURRENT_TIMESTAMP, updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE wallets (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL,
  wallet_type TEXT NOT NULL, currency TEXT NOT NULL DEFAULT 'VND', cached_balance NUMERIC NOT NULL DEFAULT 0,
  earmarked_balance NUMERIC NOT NULL DEFAULT 0, status TEXT NOT NULL DEFAULT 'ACTIVE', created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(user_id, name), FOREIGN KEY(user_id) REFERENCES users(id)
);
CREATE TABLE ledger_accounts (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, wallet_id INTEGER NULL,
  code TEXT NOT NULL, account_type TEXT NOT NULL, UNIQUE(user_id, code),
  FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(wallet_id) REFERENCES wallets(id)
);
CREATE TABLE categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL,
  category_type TEXT NOT NULL, archived_at TEXT NULL
);
CREATE TABLE financial_transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, category_id INTEGER NULL,
  transaction_type TEXT NOT NULL, status TEXT NOT NULL, description TEXT NOT NULL, currency TEXT NOT NULL,
  amount NUMERIC NOT NULL, fx_rate_snapshot NUMERIC NOT NULL, occurred_on TEXT NOT NULL, posted_at TEXT NULL,
  original_transaction_id INTEGER NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE ledger_entries (
  id INTEGER PRIMARY KEY AUTOINCREMENT, transaction_id INTEGER NOT NULL, ledger_account_id INTEGER NOT NULL,
  entry_side TEXT NOT NULL, amount_original NUMERIC NOT NULL, currency TEXT NOT NULL,
  amount_base NUMERIC NOT NULL, fx_rate_snapshot NUMERIC NOT NULL, created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(transaction_id) REFERENCES financial_transactions(id), FOREIGN KEY(ledger_account_id) REFERENCES ledger_accounts(id)
);
CREATE TABLE idempotency_keys (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, request_key TEXT NOT NULL,
  request_hash TEXT NOT NULL, transaction_id INTEGER NULL, result_code INTEGER NULL, response_json TEXT NULL,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, request_key)
);
CREATE TABLE recurring_schedules (
  id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, wallet_id INTEGER NOT NULL,
  category_id INTEGER NULL, name TEXT NOT NULL, amount NUMERIC NOT NULL, currency TEXT NOT NULL,
  frequency TEXT NOT NULL, day_of_month INTEGER NOT NULL, next_due_date TEXT NOT NULL,
  execution_mode TEXT NOT NULL, status TEXT NOT NULL, FOREIGN KEY(wallet_id) REFERENCES wallets(id)
);
CREATE TABLE exchange_rates (
  id INTEGER PRIMARY KEY AUTOINCREMENT, from_currency TEXT NOT NULL, to_currency TEXT NOT NULL,
  rate NUMERIC NOT NULL, effective_at TEXT NOT NULL, provider TEXT NULL
);
INSERT INTO users (id, full_name, email, password_hash, role, status, base_currency) VALUES
  (1, 'User One', 'one@example.test', 'x', 'USER', 'ACTIVE', 'VND'),
  (2, 'User Two', 'two@example.test', 'x', 'USER', 'ACTIVE', 'VND'),
  (3, 'Admin', 'admin@example.test', 'x', 'ADMIN', 'ACTIVE', 'VND');
INSERT INTO exchange_rates (from_currency, to_currency, rate, effective_at, provider) VALUES
  ('USD', 'VND', 25400, '2026-10-16 10:00:00', 'SEED'),
  ('EUR', 'VND', 27600, '2026-10-16 10:00:00', 'SEED');
SQL);
    }
}

exit((new WalletTestRunner())->run());
