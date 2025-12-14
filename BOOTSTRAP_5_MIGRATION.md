# Bootstrap 5 Migration Notes

## Package Updates Applied
The following security and dependency updates have been applied to `package.json`:

### Critical Security Updates
- **axios**: 0.21 → 1.7.0 (Fixes CVE-2021-3749, CVE-2023-45857)
- **lodash**: 4.17.19 → 4.17.21 (Fixes prototype pollution vulnerabilities)
- **jquery**: 3.2 → 3.7.0 (Security and compatibility updates)
- **postcss**: 8.1.14 → 8.4.31 (Security updates)

### Major Upgrades
- **bootstrap**: 4.0.0 → 5.3.0 (Major version - see breaking changes below)
- **@popperjs/core**: 2.11.8 (New - replaces popper.js, required for Bootstrap 5)
- **resolve-url-loader**: 2.3.1 → 5.0.0 (Webpack 5 compatibility)

### Framework Updates
- **laravel-mix**: 6.0.6 → 6.0.49 (Bug fixes and stability)
- **vue**: 2.5.17 → 2.7.16 (Latest Vue 2.x with Composition API backport)
- **vue-template-compiler**: 2.6.10 → 2.7.16 (Must match Vue version)

## Bootstrap 5 Breaking Changes

After running `npm install`, you will need to update your Blade templates to accommodate Bootstrap 5 changes:

### 1. Popper.js Replacement
```diff
- Old: popper.js v1.x
+ New: @popperjs/core v2.x
```

### 2. jQuery No Longer Required
Bootstrap 5 removed jQuery dependency. If your custom JavaScript relies on jQuery with Bootstrap, you may need to refactor or keep jQuery explicitly.

### 3. Class Name Changes

#### Form Controls
```diff
- <div class="form-group">
+ <div class="mb-3">

- <input class="form-control-file">
+ <input class="form-control">

- <input class="custom-select">
+ <select class="form-select">

- <label class="custom-control-label">
+ <label class="form-check-label">

- <div class="custom-control custom-checkbox">
+ <div class="form-check">

- <input class="custom-control-input">
+ <input class="form-check-input">
```

#### Utility Classes
```diff
- <div class="ml-3">  (margin-left)
+ <div class="ms-3">  (margin-start)

- <div class="mr-3">  (margin-right)
+ <div class="me-3">  (margin-end)

- <div class="pl-3">  (padding-left)
+ <div class="ps-3">  (padding-start)

- <div class="pr-3">  (padding-right)
+ <div class="pe-3">  (padding-end)

- <div class="float-left">
+ <div class="float-start">

- <div class="float-right">
+ <div class="float-end">

- <div class="text-left">
+ <div class="text-start">

- <div class="text-right">
+ <div class="text-end">
```

#### Badges
```diff
- <span class="badge badge-primary">
+ <span class="badge bg-primary">

- <span class="badge badge-pill">
+ <span class="badge rounded-pill">
```

#### Modals
```diff
- data-toggle="modal"
+ data-bs-toggle="modal"

- data-target="#myModal"
+ data-bs-target="#myModal"

- data-dismiss="modal"
+ data-bs-dismiss="modal"
```

#### Dropdowns
```diff
- data-toggle="dropdown"
+ data-bs-toggle="dropdown"
```

#### Tooltips & Popovers
```diff
- data-toggle="tooltip"
+ data-bs-toggle="tooltip"

- data-toggle="popover"
+ data-bs-toggle="popover"
```

#### Cards
```diff
- <div class="card-deck">
+ <div class="row row-cols-1 row-cols-md-3 g-4">
    <div class="col">
      <div class="card">...</div>
    </div>
  </div>

- <div class="card-columns">
+ Use CSS Grid or row-cols classes instead
```

### 4. JavaScript Changes

#### Initialization
Bootstrap 5 components must be initialized with new namespace:

```javascript
// Old Bootstrap 4
$('#myModal').modal('show');
$('[data-toggle="tooltip"]').tooltip();

// New Bootstrap 5 (vanilla JS)
var myModal = new bootstrap.Modal(document.getElementById('myModal'));
myModal.show();

var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl);
});
```

### 5. Grid Changes
```diff
- <div class="form-row">
+ <div class="row g-2">  (g-2 adds gap)

- No gutters: .no-gutters
+ No gutters: .g-0
```

### 6. Icons
Bootstrap 5 no longer includes Glyphicons. You'll need to:
- Use Bootstrap Icons: https://icons.getbootstrap.com/
- Or keep using Font Awesome if already in use
- Or use another icon library

## Migration Steps

### 1. Install Updated Dependencies
```bash
npm install
```

### 2. Find and Replace Patterns
Run these searches across your Blade templates (`resources/views`):

```bash
# Search for Bootstrap 4 specific classes
grep -r "form-group" resources/views/
grep -r "ml-\|mr-\|pl-\|pr-" resources/views/
grep -r "data-toggle" resources/views/
grep -r "badge badge-" resources/views/
grep -r "custom-control" resources/views/
```

### 3. Test Critical User Flows
After updating templates, test:
- Registration/Login forms
- User profile updates
- Payment forms (critical!)
- Message/chat interface
- Admin panel
- Modal dialogs
- Tooltips and popovers
- Dropdown menus

### 4. Update JavaScript Initialization
Search for jQuery-based Bootstrap initializations:
```bash
grep -r "\$.*modal\|tooltip\|popover\|dropdown" resources/js/
```

### 5. Check Custom CSS
Review custom CSS for Bootstrap 4 overrides that may need updating:
```bash
grep -r "\.form-group\|\.ml-\|\.mr-\|\.badge-" resources/sass/ public/css/
```

## Files Most Likely to Need Updates

Based on typical Laravel applications, focus on:

1. **Layouts**
   - `resources/views/layouts/app.blade.php`
   - `resources/views/layouts/admin.blade.php`

2. **Auth Views**
   - `resources/views/auth/login.blade.php`
   - `resources/views/auth/register.blade.php`
   - All files in `resources/views/auth/`

3. **Forms**
   - User profile forms
   - Payment forms
   - Upload forms
   - Settings forms

4. **Admin Panel**
   - All admin views
   - Data tables
   - Filter forms

5. **Modals**
   - Tip modals
   - Message modals
   - Confirmation dialogs

## Rollback Plan

If issues arise, you can rollback by:

1. Revert `package.json`:
```bash
git checkout HEAD~1 -- package.json
npm install
```

2. Or keep security updates but downgrade Bootstrap:
```json
{
  "devDependencies": {
    "axios": "^1.7.0",
    "bootstrap": "^4.6.2",
    "jquery": "^3.7.0",
    "popper.js": "^1.16.1",
    "lodash": "^4.17.21",
    "laravel-mix": "^6.0.49"
  }
}
```

## Testing Checklist

- [ ] All forms render correctly
- [ ] Form validation displays properly
- [ ] Modals open/close correctly
- [ ] Dropdowns function
- [ ] Tooltips/popovers display
- [ ] Responsive layout works on mobile
- [ ] Payment forms work (CRITICAL)
- [ ] File uploads work
- [ ] Admin panel displays correctly
- [ ] User dashboard loads
- [ ] Chat/messaging interface works
- [ ] No JavaScript console errors

## Resources

- [Bootstrap 5 Migration Guide](https://getbootstrap.com/docs/5.3/migration/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/getting-started/introduction/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)
- [Axios 1.x Migration](https://github.com/axios/axios/blob/v1.x/UPGRADE_GUIDE.md)

## Next Steps

1. Run `npm install` to update dependencies
2. Run `npm run dev` to compile assets
3. Use the search patterns above to find Bootstrap 4 code
4. Systematically update views one section at a time
5. Test thoroughly before deploying to production
