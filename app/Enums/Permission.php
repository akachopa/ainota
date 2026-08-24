<?php

namespace App\Enums;

enum Permission: string
{
    case DocumentUpload = 'document.upload';
    case DocumentViewOwn = 'document.view_own';
    case DocumentViewAll = 'document.view_all';
    case DocumentEdit = 'document.edit';
    case TransactionReview = 'transaction.review';
    case TransactionApprove = 'transaction.approve';
    case TransactionExport = 'transaction.export';
    case CoaView = 'coa.view';
    case CoaManage = 'coa.manage';
    case VendorManage = 'vendor.manage';
    case MemberManage = 'member.manage';
    case WorkspaceManage = 'workspace.manage';
    case WorkspaceDelete = 'workspace.delete';
    case BillingManage = 'billing.manage';
    case OwnershipTransfer = 'workspace.transfer';
    case UploadLinkManage = 'upload_link.manage';

    public function label(): string
    {
        return $this->value;
    }
}
