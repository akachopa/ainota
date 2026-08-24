<?php

namespace App\Enums;

enum WorkspaceRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Uploader = 'uploader';
    case Reviewer = 'reviewer';
    case Approver = 'approver';
    case Viewer = 'viewer';

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Owner => Permission::cases(),
            self::Admin => [
                Permission::DocumentUpload,
                Permission::DocumentViewOwn,
                Permission::DocumentViewAll,
                Permission::DocumentEdit,
                Permission::TransactionReview,
                Permission::TransactionApprove,
                Permission::TransactionExport,
                Permission::CoaView,
                Permission::CoaManage,
                Permission::VendorManage,
                Permission::MemberManage,
                Permission::WorkspaceManage,
                Permission::UploadLinkManage,
            ],
            self::Uploader => [
                Permission::DocumentUpload,
                Permission::DocumentViewOwn,
            ],
            self::Reviewer => [
                Permission::DocumentUpload,
                Permission::DocumentViewOwn,
                Permission::DocumentViewAll,
                Permission::DocumentEdit,
                Permission::TransactionReview,
                Permission::CoaView,
                Permission::VendorManage,
            ],
            self::Approver => [
                Permission::DocumentViewOwn,
                Permission::DocumentViewAll,
                Permission::TransactionApprove,
                Permission::TransactionExport,
                Permission::CoaView,
            ],
            self::Viewer => [
                Permission::DocumentViewAll,
                Permission::CoaView,
            ],
        };
    }

    public function has(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Uploader => 'Uploader',
            self::Reviewer => 'Reviewer',
            self::Approver => 'Approver',
            self::Viewer => 'Viewer',
        };
    }
}
