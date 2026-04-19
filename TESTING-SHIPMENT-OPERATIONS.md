# Shipment Operations Testing Guide

## 📋 Overview

This guide helps you test the Shipment Operations and COD Settlement features implemented on the `feature/cod-settlement-core` branch.

## 🎯 Quick Test

### Option 1: Manual Testing in Browser (Recommended for now)

1. **Open the admin login page:**
   ```
   http://localhost:8001/admin/login
   ```

2. **Login with:**
   - **Username:** `ceo@astgd.com`
   - **Password:** `vzxw123!`

3. **Navigate to Shipment Operations:**
   ```
   http://localhost:8001/admin/sales/shipment-operations
   ```

4. **Check these features:**

   ✓ **Settlement Status Display**
   - Can you see pending settlements?
   - Can you see settled payments?
   - Are amounts displayed correctly?

   ✓ **Operations Table/List**
   - Are shipments listed?
   - Can you see COD amounts?
   - Are timestamps showing?

   ✓ **Action Buttons**
   - Can you settle pending COD payments?
   - Can you view settlement details?
   - Are batch operations available?

   ✓ **Event Timeline**
   - Is there a history of settlement events?
   - Are status changes recorded?
   - Are timestamps accurate?

   ✓ **Batch Operations**
   - Can you settle multiple shipments at once?
   - Is there a bulk status update feature?

### Option 2: Automated Testing with Playwright

#### Setup (one time)

```bash
# Install dependencies
npm install

# Make test script executable
chmod +x test-shipment.sh
```

#### Run Tests

```bash
# Simple run
./test-shipment.sh

# Or directly with npm
npx playwright test test-shipment-operations.spec.ts --headed

# Or non-headed (background)
npx playwright test test-shipment-operations.spec.ts

# View full HTML report
npx playwright show-report
```

## 📊 What the Test Checks

The automated test (`test-shipment-operations.spec.ts`) verifies:

1. ✓ Admin login with provided credentials
2. ✓ Navigation to shipment operations page
3. ✓ Page title and main content visibility
4. ✓ Settlement status section
5. ✓ Operations table/list display
6. ✓ Action buttons available
7. ✓ Settlement data fields (amounts, dates)
8. ✓ Event timeline/history
9. ✓ Batch operations functionality
10. ✓ Status tracking system
11. ✓ Settlement reconciliation features

## 🔍 Manual Checklist

When testing manually, verify these Codex-implemented features:

### Shipment Operations Page
- [ ] Page loads without errors
- [ ] Navigation menu shows "Sales > Shipment Operations"
- [ ] Page title shows "Shipment Operations" or similar

### COD Settlement Features
- [ ] Settlement status section visible
- [ ] Shows "Pending" settlements
- [ ] Shows "Settled" payments
- [ ] Amounts are displayed correctly (e.g., ₹1,000.00)
- [ ] Settlement dates shown

### Operations Table
- [ ] Table displays shipments
- [ ] Columns show: Shipment ID, Order ID, COD Amount, Status, Date
- [ ] Multiple records listed (if available)
- [ ] Pagination working (if >10 records)

### Actions Available
- [ ] "Mark as Settled" button available
- [ ] "View Details" button available
- [ ] Batch select checkboxes (top-left of table)
- [ ] "Settle Selected" bulk action button

### Event Timeline
- [ ] Timeline visible on shipment detail page
- [ ] Shows "Created" event with timestamp
- [ ] Shows "Settled" event if applicable
- [ ] Shows "Pending" status events
- [ ] Events have clear timestamps

### Status Tracking
- [ ] Status shows: Pending, In Transit, Delivered, Settled
- [ ] Status changes are reflected in timeline
- [ ] Color coding for different statuses (visual feedback)

### Batch Operations
- [ ] Select multiple shipments with checkboxes
- [ ] Bulk "Settle" action available
- [ ] Confirmation dialog before bulk action
- [ ] Success message after bulk settle

### Data Reconciliation
- [ ] Settlement amount matches order total
- [ ] No duplicate settlements
- [ ] Correct status transitions

## 🐛 Troubleshooting

### Test won't run
```bash
# Make sure server is running on port 8001
php artisan serve --port=8001

# Or Docker
docker-compose up
```

### Login fails
- Verify credentials are correct: `ceo@astgd.com` / `vzxw123!`
- Check database has this user
- Clear browser cache/cookies

### Page not found (404)
- Route `admin/sales/shipment-operations` may not exist
- Check if feature was merged
- Verify database migrations ran

### Elements not found
- Selectors may have changed in theme/UI
- Check HTML in browser DevTools
- Update selectors in test file

## 📈 Expected Results

### After successful login:
```
✓ Login successful
📦 Navigating to Shipment Operations...
✓ Main content visible
✓ Operations table/list
✓ Settlement status section
✓ Event timeline/history
✓ Status tracking
✓ Batch operations
✓ Settlement reconciliation
```

## 💡 Tips

- Use browser DevTools (F12) to inspect elements
- Check console for JavaScript errors
- Look at Network tab for API calls
- Test both single and batch operations
- Try settling one shipment, then check if timeline updates

## 📚 Files

- `test-shipment-operations.spec.ts` - Automated test file
- `test-shipment.sh` - Test runner script
- `shipment-operations.png` - Screenshot (generated after test)
- `test-results/` - Test output folder
- `playwright-report/` - HTML test report

## Next Steps

After testing locally:
1. Document any issues found
2. Take screenshots of each working feature
3. Verify all 8 items in manual checklist
4. Run full test suite: `npm run test`
5. Commit findings to branch

---

**Current Branch:** `feature/cod-settlement-core`
**Test Date:** April 18, 2026
**Tester:** Codex Implementation Verification
