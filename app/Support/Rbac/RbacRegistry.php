<?php

namespace App\Support\Rbac;

final class RbacRegistry
{
    public const SUPER_ADMIN = 'Super Admin';

    public const ADMIN = 'Admin';

    public const TEAM = 'Tim';

    public const GUARDS = [
        'web',
        'api',
    ];

    public const ROLES = [
        self::SUPER_ADMIN,
        self::ADMIN,
        self::TEAM,
    ];

    public const PERMISSIONS = [
        'access.view',
        'access.assign-role',
        'access.assign-permission',
        'access.assign-scope',
        'user.view',
        'user.create',
        'user.update',
        'user.delete',
        'company.view',
        'company.create',
        'company.update',
        'company.delete',
        'brand.view',
        'brand.create',
        'brand.update',
        'brand.delete',
        'campaign.view',
        'campaign.create',
        'campaign.update',
        'campaign.delete',
        'campaign.approve',
        'promotion.view',
        'promotion.create',
        'promotion.update',
        'promotion.delete',
        'promotion.approve',
        'task.view',
        'task.create',
        'task.update',
        'task.delete',
        'task.review',
        'performance-report.view',
        'performance-report.create',
        'performance-report.update',
        'performance-report.delete',
        'performance-report.review',
        'performance-report.publish',
        'product.view',
        'product.create',
        'product.update',
        'product.delete',
        'variant.view',
        'variant.create',
        'variant.update',
        'variant.delete',
        'activity.view',
        'report.export',
        'settings.view',
        'settings.update',
        'system.monitor',
        'audit.view',
    ];

    public const ADMIN_PERMISSIONS = [
        'access.view',
        'access.assign-permission',
        'access.assign-scope',
        'user.view',
        'user.create',
        'user.update',
        'company.view',
        'company.create',
        'company.update',
        'company.delete',
        'brand.view',
        'brand.create',
        'brand.update',
        'brand.delete',
        'campaign.view',
        'campaign.create',
        'campaign.update',
        'campaign.delete',
        'campaign.approve',
        'promotion.view',
        'promotion.create',
        'promotion.update',
        'promotion.delete',
        'promotion.approve',
        'task.view',
        'task.create',
        'task.update',
        'task.delete',
        'task.review',
        'performance-report.view',
        'performance-report.create',
        'performance-report.update',
        'performance-report.delete',
        'performance-report.review',
        'performance-report.publish',
        'product.view',
        'product.create',
        'product.update',
        'product.delete',
        'variant.view',
        'variant.create',
        'variant.update',
        'variant.delete',
        'activity.view',
        'report.export',
    ];

    public const TEAM_DEFAULT_PERMISSIONS = [
        'company.view',
        'brand.view',
        'campaign.view',
        'campaign.create',
        'campaign.update',
        'campaign.delete',
        'promotion.view',
        'promotion.create',
        'promotion.update',
        'promotion.delete',
        'task.view',
        'task.create',
        'task.update',
        'task.delete',
        'performance-report.view',
        'performance-report.create',
        'performance-report.update',
        'performance-report.delete',
        'product.view',
        'product.create',
        'product.update',
        'product.delete',
        'variant.view',
        'variant.create',
        'variant.update',
        'variant.delete',
        'activity.view',
        'report.export',
    ];

    public const TEAM_ALLOWED_PERMISSIONS = self::TEAM_DEFAULT_PERMISSIONS;

    /** @return array<int, string> */
    public static function configurablePermissions(string $role): array
    {
        return match ($role) {
            self::SUPER_ADMIN => self::PERMISSIONS,
            self::ADMIN => self::ADMIN_PERMISSIONS,
            self::TEAM => self::TEAM_ALLOWED_PERMISSIONS,
            default => [],
        };
    }

    public static function isFinalRole(string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    public static function isEditableRole(string $role): bool
    {
        return in_array($role, [self::ADMIN, self::TEAM], true);
    }
}
