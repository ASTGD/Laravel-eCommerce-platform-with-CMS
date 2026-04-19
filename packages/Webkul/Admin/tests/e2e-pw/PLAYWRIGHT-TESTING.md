# Browser Testing with Playwright

This document explains how to run browser tests for the shipment event timeline feature and verify admin functionality.

## Quick Start

### 1. Ensure your environment is running

```bash
# From project root
php artisan serve
```

Or use Docker:
```bash
docker-compose up
```

**Ensure `APP_URL` is set in `.env`** (e.g., `http://localhost:8000`)

### 2. Run Playwright tests

```bash
# From project root
npx playwright test --config=packages/Webkul/Admin/tests/e2e-pw/playwright.config.ts
```

Or with a specific test file:
```bash
npx playwright test packages/Webkul/Admin/tests/e2e-pw/tests/shipment-event-timeline.spec.ts
```

### 3. Run tests in UI mode (recommended for development)

```bash
npx playwright test --config=packages/Webkul/Admin/tests/e2e-pw/playwright.config.ts --ui
```

This opens an interactive test runner where you can:
- See live test execution
- Step through tests
- Inspect elements
- View network logs

### 4. Run tests in headed mode (see browser)

```bash
npx playwright test --config=packages/Webkul/Admin/tests/e2e-pw/playwright.config.ts --headed
```

## Test Files

- **`shipment-event-timeline.spec.ts`** - Tests for shipment event timeline feature
  - Admin login verification
  - Orders page navigation
  - Shipment section display
  - Event timeline rendering
  - Task completion markers

- **`auth.spec.ts`** - Admin authentication tests
- **`cms.spec.ts`** - CMS functionality tests
- **`sales.spec.ts`** - Sales/Orders tests

## What the Tests Do

The shipment event timeline tests verify:

1. ✓ Admin can log in with credentials
2. ✓ Can navigate to Sales → Orders
3. ✓ Orders list displays correctly
4. ✓ Shipment section appears on order detail page
5. ✓ Event timeline component renders
6. ✓ Timeline items contain expected data (dates, statuses)
7. ✓ Error handling works properly

## Viewing Test Results

After running tests, Playwright generates:

- **HTML Report**: `packages/Webkul/Admin/tests/e2e-pw/playwright-report/`
  ```bash
  npx playwright show-report
  ```

- **Screenshots**: Taken on failure automatically
- **Videos**: Recorded on failure (check `test-results/`)

## Admin Test Credentials

Default test credentials (from `utils/admin.ts`):
- **Email**: `admin@example.com`
- **Password**: `admin123`

Ensure these exist in your database:
```bash
php artisan tinker
```

```php
$admin = \Webkul\Admin\Models\Admin::firstOrCreate(
    ['email' => 'admin@example.com'],
    ['name' => 'Test Admin', 'password' => bcrypt('admin123')]
);
```

## Authentication Caching

Playwright caches admin login state in:
```
packages/Webkul/Admin/tests/e2e-pw/.state/admin-auth.json
```

To force re-login:
```bash
rm packages/Webkul/Admin/tests/e2e-pw/.state/admin-auth.json
```

## Environment Setup

Ensure your `.env` file has:
```env
APP_URL=http://localhost:8000
APP_ENV=testing
APP_DEBUG=false
DB_DATABASE=your_test_db
```

For isolated testing, use a test database:
```env
DB_DATABASE=laravel_test
```

## Common Issues

### Tests fail with "No such element"
- Ensure the Laravel app is running
- Check `APP_URL` in `.env`
- Verify database is seeded with test data

### "Admin login failed"
- Check credentials in `utils/admin.ts`
- Verify admin user exists in database
- Clear auth cache: `rm .state/admin-auth.json`

### Timeout errors
- Increase timeout in `playwright.config.ts`
- Check browser network tab in headed mode
- View logs: `npx playwright test --config=... --debug`

## Advanced: Custom Test

Create a new test file:
```typescript
// packages/Webkul/Admin/tests/e2e-pw/tests/my-feature.spec.ts
import { test, expect } from "../setup";

test.describe("My Feature", () => {
    test("should do something", async ({ adminPage }) => {
        await adminPage.goto("admin/my-feature");
        await expect(adminPage.locator("h1")).toHaveText("My Feature");
    });
});
```

Then run it:
```bash
npx playwright test packages/Webkul/Admin/tests/e2e-pw/tests/my-feature.spec.ts
```

## CI/CD Integration

For GitHub Actions, add to `.github/workflows/test.yml`:
```yaml
- name: Run Playwright tests
  run: |
    npx playwright install
    npx playwright test --config=packages/Webkul/Admin/tests/e2e-pw/playwright.config.ts
```

## Documentation

- [Playwright Official Docs](https://playwright.dev)
- [Playwright Test API](https://playwright.dev/docs/api/class-test)
- [Selectors Guide](https://playwright.dev/docs/selectors)
