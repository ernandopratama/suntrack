<?php

namespace App\Services\Workflow;

use App\Models\User;
use App\Services\Authorization\DataScopeService;
use App\Support\Rbac\RbacRegistry;
use Illuminate\Validation\ValidationException;

class WorkflowAssignmentService
{
    public function __construct(private DataScopeService $dataScope) {}

    public function isManager(User $user): bool
    {
        return $user->hasAnyRole([RbacRegistry::SUPER_ADMIN, RbacRegistry::ADMIN]);
    }

    public function assertManager(User $user): void
    {
        abort_unless($this->isManager($user), 403, 'Only Super Admin or Admin may change workflow ownership.');
    }

    public function assertPic(?string $userId): void
    {
        if ($userId === null) {
            return;
        }

        $pic = User::findOrFail($userId);
        if (! $this->isManager($pic)) {
            throw ValidationException::withMessages(['pic_id' => 'PIC must have the Super Admin or Admin role.']);
        }
    }

    public function assertTeamMember(string $userId, string $brandId, string $field = 'assignee_id'): void
    {
        $member = User::findOrFail($userId);

        if (! $member->hasRole(RbacRegistry::TEAM)) {
            throw ValidationException::withMessages([$field => 'Assigned user must have the Tim role.']);
        }

        if (! $this->dataScope->canAccessBrandId($member, $brandId)) {
            throw ValidationException::withMessages([$field => 'Assigned Tim user is outside the selected Brand scope.']);
        }
    }

    /** @param array<int, string> $userIds */
    public function assertTeamMembers(array $userIds, string $brandId): void
    {
        foreach ($userIds as $userId) {
            $this->assertTeamMember($userId, $brandId, 'member_ids');
        }
    }

    public function assertAuthor(string $userId, string $brandId): void
    {
        $author = User::findOrFail($userId);

        if (! $author->hasAnyRole(RbacRegistry::ROLES)) {
            throw ValidationException::withMessages(['author_id' => 'Author must have an internal SUNTRACK role.']);
        }

        if (! $this->dataScope->canAccessBrandId($author, $brandId)) {
            throw ValidationException::withMessages(['author_id' => 'Author is outside the selected Brand scope.']);
        }
    }
}
