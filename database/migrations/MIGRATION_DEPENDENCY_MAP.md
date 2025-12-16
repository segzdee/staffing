# Migration Dependency Map

This document tracks all foreign key dependencies to ensure migrations run in correct order.

## Core Tables (Must be created first)
- `users` - Created: `2014_10_12_000000`
- `worker_profiles` - Created: `2025_12_13_220001` (depends on: users)
- `business_profiles` - Created: `2025_12_13_220002` (depends on: users)
- `agency_profiles` - Created: `2025_12_13_220003` (depends on: users)
- `shifts` - Created: `2025_12_14_000001` (depends on: users, business_profiles, shift_templates)
- `shift_assignments` - Created: `2025_12_14_010002` (depends on: shifts, users)
- `skills` - Created: `2025_12_13_220006`
- `certifications` - Created: `2025_12_13_220007` (depends on: users)

## Dependency Chain Analysis

### Tables that depend on `users`:
- worker_profiles, business_profiles, agency_profiles
- worker_skills, worker_certifications
- shift_applications, shift_assignments
- All profile-related tables

### Tables that depend on `shifts`:
- shift_assignments
- shift_applications
- shift_payments
- shift_swaps
- shift_invitations
- shift_attachments
- shift_notifications

### Tables that depend on `shift_assignments`:
- shift_payments
- ratings
- shift_swaps

### Tables that depend on `onboarding_steps`:
- onboarding_progress (300016) - MUST run after 300015

### Tables that depend on `right_to_work_verifications`:
- rtw_documents (300025) - MUST run after 300024

### Tables that depend on `background_checks`:
- background_check_consents (300037) - MUST run after 300019

### Tables that depend on `certification_types`:
- skill_certification_requirements (300032) - MUST run after 300014

### Tables that depend on `agency_performance_scorecards`:
- agency_performance_notifications (080003) - MUST run after 080001

## Migration Order Verification

All migrations are now ordered correctly. No duplicate timestamps remain.
