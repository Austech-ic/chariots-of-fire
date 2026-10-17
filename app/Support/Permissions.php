<?php

namespace App\Support;

/**
 * Permission catalogue and default role bundles. Counselling permissions are
 * deliberately excluded from general administrator roles.
 */
class Permissions
{
    public const ALL = [
        'Content' => [
            'content.edit' => 'Create and edit pages, homepage, mandates',
            'content.publish' => 'Publish, schedule and archive content',
            'media.manage' => 'Upload and manage media',
            'sermons.manage' => 'Manage sermons, series, speakers',
            'events.manage' => 'Manage events and registrations',
            'livestreams.manage' => 'Manage livestreams and live status',
            'gallery.manage' => 'Manage gallery albums',
            'translations.manage' => 'Manage languages and translations',
        ],
        'Counselling (confidential)' => [
            'counselling.coordinate' => 'Read, assign and schedule all counselling requests',
            'counselling.counsel' => 'Read counselling requests assigned to me',
            'counselling.configure' => 'Configure services, counsellors and availability',
        ],
        'Giving' => [
            'donations.view' => 'View donations and reconciliation',
            'donations.refund' => 'Issue refunds',
            'donations.export' => 'Export donation records',
        ],
        'Community' => [
            'newsletter.manage' => 'Manage newsletter subscriptions and consent',
            'enquiries.manage' => 'Handle contact enquiries',
            'members.manage' => 'Manage member accounts and resources',
        ],
        'System' => [
            'settings.manage' => 'Site settings and integrations',
            'users.manage' => 'Manage staff accounts and roles',
            'audit.view' => 'View audit logs and webhook events',
        ],
    ];

    public const ROLES = [
        'super-admin' => ['name' => 'Super administrator', 'permissions' => '*'],
        'administrator' => ['name' => 'Administrator', 'permissions' => [
            'content.edit', 'content.publish', 'media.manage', 'sermons.manage', 'events.manage', 'livestreams.manage',
            'gallery.manage', 'translations.manage', 'newsletter.manage', 'enquiries.manage', 'members.manage',
            'settings.manage', 'users.manage', 'audit.view',
        ]],
        'publisher' => ['name' => 'Publisher', 'permissions' => [
            'content.edit', 'content.publish', 'media.manage', 'sermons.manage', 'events.manage', 'livestreams.manage', 'gallery.manage', 'translations.manage',
        ]],
        'editor' => ['name' => 'Content editor', 'permissions' => [
            'content.edit', 'media.manage', 'sermons.manage', 'events.manage', 'gallery.manage', 'translations.manage',
        ]],
        'counselling-coordinator' => ['name' => 'Counselling coordinator', 'permissions' => ['counselling.coordinate', 'counselling.configure']],
        'counsellor' => ['name' => 'Counsellor', 'permissions' => ['counselling.counsel']],
        'finance' => ['name' => 'Finance administrator', 'permissions' => ['donations.view', 'donations.refund', 'donations.export']],
        'communications' => ['name' => 'Communications', 'permissions' => ['newsletter.manage', 'enquiries.manage']],
    ];

    public static function flat(): array
    {
        return array_merge(...array_values(self::ALL));
    }
}
