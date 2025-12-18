# Landing Pages vs Routes - Complete List

## Homepage
| Route | URL | View File | Route Name |
|-------|-----|-----------|------------|
| `/` | `https://overtimestaff.com/` | `resources/views/welcome.blade.php` | `home` |

## General Marketing Pages
| Route | URL | View File | Route Name |
|-------|-----|-----------|------------|
| `/features` | `https://overtimestaff.com/features` | `resources/views/public/features.blade.php` | `features` |
| `/pricing` | `https://overtimestaff.com/pricing` | `resources/views/public/pricing.blade.php` | `pricing` |
| `/about` | `https://overtimestaff.com/about` | `resources/views/public/about.blade.php` | `about` |
| `/contact` | `https://overtimestaff.com/contact` | `resources/views/public/contact.blade.php` | `contact` |
| `/terms` | `https://overtimestaff.com/terms` | `resources/views/public/terms.blade.php` | `terms` |
| `/privacy` | `https://overtimestaff.com/privacy` | `resources/views/public/privacy.blade.php` | `privacy` |

## Worker Landing Pages
| Route | URL | View File | Route Name |
|-------|-----|-----------|------------|
| `/workers/find-shifts` | `https://overtimestaff.com/workers/find-shifts` | `resources/views/public/workers/find-shifts.blade.php` | `workers.find-shifts` |
| `/workers/features` | `https://overtimestaff.com/workers/features` | `resources/views/public/workers/features.blade.php` | `workers.features` |
| `/workers/get-started` | `https://overtimestaff.com/workers/get-started` | `resources/views/public/workers/get-started.blade.php` | `workers.get-started` |

## Business Landing Pages
| Route | URL | View File | Route Name |
|-------|-----|-----------|------------|
| `/business/find-staff` | `https://overtimestaff.com/business/find-staff` | `resources/views/public/business/find-staff.blade.php` | `business.find-staff` |
| `/business/pricing` | `https://overtimestaff.com/business/pricing` | `resources/views/public/business/pricing.blade.php` | `business.pricing` |
| `/business/post-shifts` | `https://overtimestaff.com/business/post-shifts` | `resources/views/public/business/post-shifts.blade.php` | `business.post-shifts` |

## Authentication Pages (Landing/Entry Points)
| Route | URL | Controller/View | Route Name |
|-------|-----|-----------------|------------|
| `/login` | `https://overtimestaff.com/login` | `App\Http\Controllers\Auth\LoginController@showLoginForm` | `login` |
| `/register` | `https://overtimestaff.com/register` | `App\Http\Controllers\Auth\RegisterController@showRegistrationForm` | `register` |
| `/password/reset` | `https://overtimestaff.com/password/reset` | `App\Http\Controllers\Auth\ForgotPasswordController@showLinkRequestForm` | `password.request` |

## Public Profile Pages (Landing Pages)
| Route | URL | Controller | Route Name |
|-------|-----|------------|------------|
| `/profile/{username}` | `https://overtimestaff.com/profile/{username}` | `App\Http\Controllers\PublicProfileController@show` | `profile.public` |
| `/workers` | `https://overtimestaff.com/workers` | `App\Http\Controllers\PublicProfileController@searchWorkers` | `workers.search` |

## Summary

### Total Landing Pages: **13**

**By Category:**
- **Homepage**: 1
- **General Marketing**: 6
- **Worker-Specific**: 3
- **Business-Specific**: 3

**All Landing Pages:**
1. Homepage (`/`)
2. Features (`/features`)
3. Pricing (`/pricing`)
4. About (`/about`)
5. Contact (`/contact`)
6. Terms (`/terms`)
7. Privacy (`/privacy`)
8. Find Shifts (`/workers/find-shifts`)
9. Worker Features (`/workers/features`)
10. Get Started (Workers) (`/workers/get-started`)
11. Find Staff (`/business/find-staff`)
12. Business Pricing (`/business/pricing`)
13. Post Shifts (`/business/post-shifts`)

### Notes

- All landing pages extend `layouts.marketing`
- All landing pages use `<x-global-header />` and `<x-global-footer />`
- All routes use `Route::view()` for static pages (except homepage which uses a closure)
- Contact page has POST route: `contact.submit` for form submission
- All landing pages are publicly accessible (no auth middleware)

### Missing Routes

The following view file exists but has no route defined:
- `resources/views/public/help/agency.blade.php` - No route found in `routes/web.php`
