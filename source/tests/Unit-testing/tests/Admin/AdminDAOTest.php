<?php

declare(strict_types=1);

namespace WealthFlowTest\Admin;

use WealthFlowTest\Support\AdminTestCase;

final class AdminDAOTest extends AdminTestCase
{
    public function testGetPdoReturnsInjectedConnection(): void
    {
        self::assertSame($this->pdo, $this->adminDao->getPdo());
    }

    public function testGetAllDefaultCategoriesMapsAndFiltersRows(): void
    {
        $all = $this->adminDao->getAllDefaultCategories();
        $active = $this->adminDao->getAllDefaultCategories(true);

        self::assertCount(3, $all);
        self::assertCount(2, $active);
        self::assertContainsOnly('array', $all);
        self::assertTrue($active[0]['isActive']);
        self::assertTrue($active[1]['isActive']);
    }

    public function testFindDefaultCategoryByIdReturnsMappedRowOrNull(): void
    {
        self::assertSame([
            'id' => 1,
            'name' => 'Lương',
            'categoryType' => 'INCOME',
            'isActive' => true,
        ], $this->adminDao->findDefaultCategoryById(1));
        self::assertNull($this->adminDao->findDefaultCategoryById(999));
    }

    public function testFindDefaultCategoryByNameAndTypeNormalizesAndExcludesId(): void
    {
        $found = $this->adminDao->findDefaultCategoryByNameAndType('  lương ', ' income ');

        self::assertNotNull($found);
        self::assertSame(1, (int) $found['id']);
        self::assertNull($this->adminDao->findDefaultCategoryByNameAndType('Lương', 'INCOME', 1));
    }

    public function testCreateDefaultCategoryTrimsAndNormalizesValues(): void
    {
        $id = $this->adminDao->createDefaultCategory('  Tiền thưởng ', ' income ', false);
        $row = $this->adminDao->findDefaultCategoryById($id);

        self::assertGreaterThan(0, $id);
        self::assertSame('Tiền thưởng', $row['name']);
        self::assertSame('INCOME', $row['categoryType']);
        self::assertFalse($row['isActive']);
    }

    public function testUpdateDefaultCategoryUpdatesSupportedFields(): void
    {
        self::assertTrue($this->adminDao->updateDefaultCategory(3, [
            'name' => '  Danh mục tái kích hoạt ',
            'categoryType' => ' income ',
            'isActive' => true,
        ]));

        $row = $this->adminDao->findDefaultCategoryById(3);
        self::assertSame('Danh mục tái kích hoạt', $row['name']);
        self::assertSame('INCOME', $row['categoryType']);
        self::assertTrue($row['isActive']);
    }

    public function testUpdateDefaultCategoryReturnsFalseForEmptyFields(): void
    {
        self::assertFalse($this->adminDao->updateDefaultCategory(1, []));
    }

    public function testDeleteDefaultCategoryRemovesRow(): void
    {
        self::assertTrue($this->adminDao->deleteDefaultCategory(3));
        self::assertNull($this->adminDao->findDefaultCategoryById(3));
    }

    public function testGetSystemConfigsReturnsKeyValueMap(): void
    {
        $configs = $this->adminDao->getSystemConfigs();

        self::assertSame('80', $configs['BUDGET_WARNING_THRESHOLD_PERCENT']);
        self::assertSame('5000', $configs['CSV_EXPORT_MAX_ROWS']);
    }

    public function testGetSystemConfigReturnsValueOrProvidedDefault(): void
    {
        self::assertSame('60', $this->adminDao->getSystemConfig('JWT_ACCESS_TOKEN_TTL_MINUTES'));
        self::assertSame('fallback', $this->adminDao->getSystemConfig('MISSING', 'fallback'));
        self::assertNull($this->adminDao->getSystemConfig('MISSING'));
    }

    public function testUpdateSystemConfigUpdatesExistingAndInsertsNewKey(): void
    {
        self::assertTrue($this->adminDao->updateSystemConfig(
            ' BUDGET_WARNING_THRESHOLD_PERCENT ',
            ' 90 '
        ));
        self::assertSame('90', $this->adminDao->getSystemConfig('BUDGET_WARNING_THRESHOLD_PERCENT'));

        self::assertTrue($this->adminDao->updateSystemConfig(' NEW_CONFIG ', ' enabled '));
        self::assertSame('enabled', $this->adminDao->getSystemConfig('NEW_CONFIG'));
    }
}
