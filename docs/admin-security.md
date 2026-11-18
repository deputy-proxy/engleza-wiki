# Admin panel security and user management

## Role matrix

| Capability | Admin | Editor | Teacher | Learner |
|---|---:|---:|---:|---:|
| Access `/admin` | Yes | Yes | No | No |
| View, create or edit users | Yes | No | No | No |
| Assign or change roles | Yes | No | No | No |
| Delete eligible accounts | Yes | No | No | No |
| Manage educational content | As granted by each resource | As granted by each resource | No | No |

User management is protected by Laravel's `UserPolicy`. Filament resource navigation visibility is not treated as an authorization boundary: the policy is checked for page access and server-side record actions, including Livewire requests. Bulk deletion performs individual record authorization.

## Account safety rules

- An administrator cannot demote or delete their own account through the user-management resource.
- The last administrator cannot be demoted or deleted. Promote and verify another administrator before changing the existing administrator's role.
- Accounts with learning attempts, enrollments, lesson progress, vocabulary/review history, adaptive practice, XP/achievements, saved resources, writing submissions, or teacher class groups cannot be deleted through this resource. Several of these tables use cascading foreign keys; blocking deletion prevents accidental destruction of educational history.
- Accounts without protected records may be deleted by an administrator. Review the account and its dependencies before deletion.
- Role changes are performed only by an authenticated administrator. The role field is omitted from non-admin form state, and the policy rejects unauthorized requests independently of the UI.

## Administrator provisioning and recovery

1. Have an existing administrator sign in and create the new account at `/admin/users/create`, assigning the `admin` role.
2. Verify the new administrator can sign in and access `/admin` before changing or removing the previous administrator.
3. Never promote an account by editing the database through an unreviewed production console command. If no administrator can access the panel, use a controlled, audited database operation through the deployment's approved access process, verify the target email and account identity, and record who performed the recovery.
4. Do not remove or demote the last administrator. The application enforces this invariant during model updates and deletes as well as through Filament authorization.

## Testing and maintenance

The `UserPolicy` is the authorization source for user-resource access. Add regression tests whenever roles or user lifecycle operations change. Review this document whenever roles, Filament resources, or user-related foreign keys change. Account deletion should remain disabled for any new user-owned history table until an explicit retention, anonymisation, or transfer policy is implemented.
