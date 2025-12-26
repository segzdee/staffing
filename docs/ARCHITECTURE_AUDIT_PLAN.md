# Architecture Audit & Refactoring Plan

## Executive Summary

This document outlines a comprehensive audit and refactoring plan to:
1. Extract business logic into Services
2. Fix critical security issues
3. Improve payment/webhook handling
4. Implement unified onboarding state machine
5. Standardize dashboard gating
6. Improve UI/UX consistency
7. Optimize performance

**Estimated Scope**: 50+ files, 10,000+ lines of code
**Priority**: Critical security fixes first, then architecture, then optimization

---

## Phase 1: Critical Security Fixes (IMMEDIATE)

### 1.1 Remove chmod 0777 Usage
**Files**: `app/Helper.php` (lines 142, 200)
**Risk**: HIGH - Allows world-writable files
**Fix**: Use 0644 or 0755 with proper ownership

### 1.2 Fix External Links Security
**Files**: 112 instances in `resources/views/**/*.blade.php`
**Risk**: MEDIUM - Missing `rel="noopener noreferrer"` on `target="_blank"`
**Fix**: Add `rel="noopener noreferrer"` to all external links

### 1.3 HTML Sanitization
**Risk**: HIGH - User input may contain XSS
**Fix**: Implement allowlist-based HTML sanitization using HTMLPurifier or similar

### 1.4 Route Security Audit
**Risk**: HIGH - Verify all payout/webhook/admin routes have proper auth
**Fix**: Audit all routes, add policies, enforce 2FA where needed

---

## Phase 2: Service Layer Architecture

### 2.1 Existing Services
- ✅ `EscrowService.php` - Exists
- ✅ `OnboardingService.php` - Exists (needs unification)
- ❌ `PaymentService.php` - Needs creation
- ❌ `MarketplaceService.php` - Needs creation
- ❌ `NotificationService.php` - Needs creation

### 2.2 Controllers Requiring Refactoring

**High Priority:**
1. `UpgradeController` - 5,800 lines, single massive function
2. `UserController` - 2,084 lines, 46 functions
3. `AdminController` - 1,928 lines, 69 functions

**Medium Priority:**
- Payment controllers with business logic
- Shift management controllers
- Dashboard controllers

### 2.3 Service Interfaces

Create clean interfaces for:
- `PaymentServiceInterface`
- `EscrowServiceInterface`
- `OnboardingServiceInterface`
- `MarketplaceServiceInterface`
- `NotificationServiceInterface`

---

## Phase 3: Payment System Improvements

### 3.1 Webhook Verification
**Current State**: Some webhooks verify signatures, some don't
**Fix**: 
- Implement consistent signature verification
- Add idempotency handling
- Create webhook event log table

### 3.2 Escrow State Machine
**Current State**: Escrow logic may be scattered
**Fix**:
- Centralize in `EscrowService`
- Implement ledger-backed state transitions
- Handle dispute/refund/replay edge cases

### 3.3 Stripe Webhook Routing
**Verify**: All Stripe webhooks route to correct handlers
**Fix**: Ensure proper routing and error handling

---

## Phase 4: Onboarding State Machine

### 4.1 Current State
- Multiple onboarding implementations (Worker, Business)
- Inconsistent state tracking
- No single source of truth

### 4.2 Unified Implementation
- Single `OnboardingService` with state machine
- Database schema alignment
- Backend/UI synchronization
- Analytics tracking per step

---

## Phase 5: Dashboard Gating Pipeline

### 5.1 Standard Pipeline
```
auth → verified → user_type → onboarding → permissions
```

### 5.2 Implementation
- Create `DashboardGate` middleware
- Apply to all dashboard routes
- Remove standalone feature pages bypassing dashboard

---

## Phase 6: UI/UX Standardization

### 6.1 Design Tokens
- Standardize colors, spacing, typography
- Create design token file

### 6.2 Component Library
- Standardize shadcn component usage
- Create reusable components

### 6.3 States
- Loading states
- Empty states
- Error states

### 6.4 Accessibility
- Focus states
- Keyboard navigation
- ARIA labels

### 6.5 Responsive Design
- Mobile/tablet/desktop breakpoints
- Test across devices

---

## Phase 7: Performance Optimization

### 7.1 Caching
- Redis caching with TTL
- Cache keys strategy
- Cache invalidation

### 7.2 Queues
- Move slow tasks to queues
- Configure Horizon

### 7.3 Database
- Eliminate N+1 queries
- Add required indexes
- Enforce pagination

### 7.4 Performance Report
- p50/p95 metrics
- Identify bottlenecks
- Fix critical issues

---

## Implementation Order

1. **Week 1**: Security fixes (Phase 1)
2. **Week 2**: Service layer creation (Phase 2)
3. **Week 3**: Payment improvements (Phase 3)
4. **Week 4**: Onboarding unification (Phase 4)
5. **Week 5**: Dashboard gating (Phase 5)
6. **Week 6**: UI/UX standardization (Phase 6)
7. **Week 7**: Performance optimization (Phase 7)
8. **Week 8**: Testing, documentation, final fixes

---

## Success Criteria

- [ ] All security issues fixed
- [ ] Business logic extracted to Services
- [ ] All services have interfaces and tests
- [ ] Payment system secure and idempotent
- [ ] Onboarding unified and deterministic
- [ ] Dashboard gating enforced
- [ ] UI/UX consistent and accessible
- [ ] Performance metrics meet targets
- [ ] All tests pass
- [ ] No lint/type errors
- [ ] CHANGELOG, TEST PLAN, ACCEPTANCE CHECKLIST created

---

## Next Steps

1. Start with Phase 1 (Security) - IMMEDIATE
2. Create service interfaces
3. Begin controller refactoring
4. Implement fixes incrementally with tests
