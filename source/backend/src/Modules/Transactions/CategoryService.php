<?php

declare(strict_types=1);

namespace WealthFlow\Modules\Transactions;

use InvalidArgumentException;
use RuntimeException;

final class CategoryService
{
    public function __construct(private ?CategoryDAO $categoryDao = null)
    {
        $this->categoryDao ??= new CategoryDAO();
    }

    /** @return array{categories: array<int, array<string, mixed>>, defaultCategories: array<int, array<string, mixed>>} */
    public function list(int $userId, ?string $type = null, bool $includeArchived = false): array
    {
        $normalizedType = $this->normalizeOptionalType($type);
        return [
            'categories' => $this->categoryDao->findAllByUser($userId, $normalizedType, $includeArchived),
            'defaultCategories' => $this->categoryDao->findDefaultCategories($normalizedType),
        ];
    }

    /** @return array<string, mixed> */
    public function create(int $userId, string $name, string $type): array
    {
        $name = $this->validateName($name);
        $type = $this->normalizeType($type);
        if ($this->categoryDao->nameExists($userId, $name, $type)) {
            throw new RuntimeException('Đã có danh mục cùng tên và cùng loại.', 409);
        }
        $id = $this->categoryDao->create($userId, $name, $type);
        return $this->categoryDao->findByIdAndUser($id, $userId)
            ?? ['id' => $id, 'name' => $name, 'category_type' => $type, 'archived_at' => null];
    }

    /** @return array<string, mixed> */
    public function rename(int $userId, int $id, string $name): array
    {
        $category = $this->categoryDao->findByIdAndUser($id, $userId);
        if ($category === null) {
            throw new RuntimeException('Danh mục không tồn tại, đã lưu trữ hoặc không thuộc quyền sở hữu.', 404);
        }
        $name = $this->validateName($name);
        $type = (string) $category['category_type'];
        if ($this->categoryDao->nameExists($userId, $name, $type, $id)) {
            throw new RuntimeException('Đã có danh mục cùng tên và cùng loại.', 409);
        }
        if (!$this->categoryDao->update($id, $userId, $name)) {
            throw new RuntimeException('Không thể cập nhật danh mục.', 409);
        }
        return $this->categoryDao->findByIdAndUser($id, $userId)
            ?? ['id' => $id, 'name' => $name, 'category_type' => $type, 'archived_at' => null];
    }

    /** @return array{categoryId: int, archived: bool, hasTransactions: bool, physicallyDeleted: bool} */
    public function archive(int $userId, int $id): array
    {
        if ($this->categoryDao->findByIdAndUser($id, $userId) === null) {
            throw new RuntimeException('Danh mục không tồn tại, đã lưu trữ hoặc không thuộc quyền sở hữu.', 404);
        }
        $hasTransactions = $this->categoryDao->hasTransactions($id, $userId);
        if (!$this->categoryDao->archive($id, $userId)) {
            throw new RuntimeException('Không thể lưu trữ danh mục.', 409);
        }
        return [
            'categoryId' => $id,
            'archived' => true,
            'hasTransactions' => $hasTransactions,
            'physicallyDeleted' => false,
        ];
    }

    private function validateName(string $name): string
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
            throw new InvalidArgumentException('Tên danh mục cần từ 1 đến 100 ký tự.');
        }
        return $name;
    }

    private function normalizeType(string $type): string
    {
        $type = strtoupper(trim($type));
        if (!in_array($type, ['INCOME', 'EXPENSE'], true)) {
            throw new InvalidArgumentException('Loại danh mục phải là INCOME hoặc EXPENSE.');
        }
        return $type;
    }

    private function normalizeOptionalType(?string $type): ?string
    {
        return $type === null || trim($type) === '' ? null : $this->normalizeType($type);
    }
}
