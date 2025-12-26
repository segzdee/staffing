# TODO Budget Policy

## Overview

This document defines the TODO budget policy for the OvertimeStaff codebase. All TODO comments must follow this policy to maintain code quality and trackability.

## Policy

**All TODO comments MUST include an issue ID in the format: `TODO(ISSUE-ID)`**

### Valid Formats

```php
// TODO(OTS-123): Refactor this method to use dependency injection
// TODO(OTS-456): Add validation for this input
// FIXME(OTS-789): This query causes N+1 problem
// XXX(OTS-101): Security review needed for this endpoint
```

### Invalid Formats

```php
// TODO: Fix this later
// TODO: Add tests
// FIXME: This is broken
// XXX: Needs refactoring
```

## Enforcement

- CI pipeline will fail if TODOs without issue IDs are found
- Code reviews should reject PRs with invalid TODO comments
- Existing TODOs without issue IDs should be migrated to issues and updated

## Migration Process

1. Create an issue in the project tracker
2. Update the TODO comment to include the issue ID
3. Link the TODO to the issue in the issue description

## Exceptions

- Test files may contain TODOs without issue IDs for test-specific notes
- Vendor files are excluded from this policy
- Temporary debugging TODOs in development branches are acceptable but must be removed before merge
