# AI Social Media Manager - User Stories & Test Cases

## 📋 USER STORIES

### 1. Account Management

#### US-1.1: Add New Account
**As a** WordPress admin
**I want to** add a new social media account
**So that** I can publish content to that platform

**Acceptance Criteria:**
- ✅ Click "Add New Account" button opens modal/form
- ✅ Select platform from dropdown (Telegram, Facebook, etc.)
- ✅ Form shows platform-specific credential fields
- ✅ Validation shows errors for missing required fields
- ✅ Success message after save
- ✅ New account appears in accounts list
- ✅ Can test connection before saving

**Test Cases:**
```
TC-1.1.1: Add Telegram Account
  1. Navigate to Social Media > Accounts
  2. Click "Add New Account"
  3. Select "Telegram" from platform dropdown
  4. Fill in "Bot Token" and "Channel ID"
  5. Click "Test Connection"
  6. Verify connection success message
  7. Click "Save Account"
  8. Verify account appears in list with "Active" status

TC-1.1.2: Validation Error
  1. Click "Add New Account"
  2. Select "Facebook"
  3. Leave required fields empty
  4. Click "Save"
  5. Verify error messages show for each required field
```

#### US-1.2: Edit Account
**As a** WordPress admin
**I want to** edit existing account credentials
**So that** I can update tokens when they expire

**Test Cases:**
```
TC-1.2.1: Edit Account Credentials
  1. Click "Edit" on existing account
  2. Modal opens with current values
  3. Update access token
  4. Click "Save"
  5. Verify success message
  6. Verify updated values
```

#### US-1.3: Delete Account
**As a** WordPress admin
**I want to** delete an account
**So that** I can remove platforms I no longer use

**Test Cases:**
```
TC-1.3.1: Delete with Confirmation
  1. Click "Delete" on account
  2. Verify confirmation dialog appears
  3. Click "Confirm Delete"
  4. Verify account removed from list
  5. Verify success message
```

---

### 2. Content Composer

#### US-2.1: AI Content Generation
**As a** content creator
**I want to** generate content using AI
**So that** I can save time creating platform-optimized posts

**Test Cases:**
```
TC-2.1.1: Generate AI Content
  1. Navigate to Social Media > Composer
  2. Enter topic "WordPress SEO tips"
  3. Select tone "Professional"
  4. Select platform "Twitter"
  5. Click "Generate with AI"
  6. Verify loading indicator
  7. Verify content appears in textarea
  8. Verify character count updates
  9. Verify content is under 280 chars for Twitter

TC-2.1.2: Generate Multiple Variations
  1. Enter topic
  2. Click "Generate 3 Variations"
  3. Verify 3 different versions appear
  4. Click on variation to use it
  5. Verify content fills textarea
```

#### US-2.2: Publish Now
**As a** content creator
**I want to** publish immediately to selected platforms
**So that** content goes live right away

**Test Cases:**
```
TC-2.2.1: Publish to Single Platform
  1. Enter/generate content
  2. Select "Telegram" platform checkbox
  3. Click "Publish"
  4. Verify loading state
  5. Verify success notification
  6. Verify post appears in dashboard as "published"

TC-2.2.2: Publish to Multiple Platforms
  1. Enter content
  2. Select "Facebook", "Twitter", "LinkedIn"
  3. Click "Publish"
  4. Verify publishing to all 3 platforms
  5. Verify 3 posts created in database
```

#### US-2.3: Schedule Post
**As a** content creator
**I want to** schedule posts for later
**So that** I can plan content in advance

**Test Cases:**
```
TC-2.3.1: Schedule for Future Date
  1. Enter content
  2. Select platform
  3. Choose "Schedule for Later"
  4. Select date/time (tomorrow 10:00)
  5. Click "Schedule"
  6. Verify success message
  7. Navigate to Calendar
  8. Verify post appears on scheduled date
```

#### US-2.4: Save Draft
**As a** content creator
**I want to** save posts as drafts
**So that** I can finish them later

**Test Cases:**
```
TC-2.4.1: Save Draft
  1. Enter partial content
  2. Click "Save as Draft"
  3. Verify saved message
  4. Reload page
  5. Verify draft in drafts list
```

---

### 3. Dashboard

#### US-3.1: View Statistics
**As a** admin
**I want to** see overview statistics
**So that** I can monitor performance

**Test Cases:**
```
TC-3.1.1: Dashboard Stats Display
  1. Navigate to Social Media > Dashboard
  2. Verify 4 stat cards visible:
     - Total Posts
     - Pending Queue
     - Active Platforms
     - Analytics Records
  3. Verify numbers are correct
  4. Verify recent posts table shows last 5 posts
  5. Verify upcoming scheduled shows next 5
```

---

### 4. Calendar

#### US-4.1: View Scheduled Posts
**As a** content manager
**I want to** see calendar view of scheduled posts
**So that** I can visualize content plan

**Test Cases:**
```
TC-4.1.1: Calendar View
  1. Navigate to Calendar
  2. Verify current month displayed
  3. Verify scheduled posts show on correct dates
  4. Click on post
  5. Verify post details modal opens
  6. Verify can reschedule from modal
```

---

### 5. Analytics

#### US-5.1: View Platform Analytics
**As a** marketer
**I want to** see analytics by platform
**So that** I can compare performance

**Test Cases:**
```
TC-5.1.1: Analytics Report
  1. Navigate to Analytics
  2. Select time period (30 days)
  3. Verify platform summary table shows:
     - Posts count
     - Impressions
     - Engagement rate
  4. Verify top posts section
  5. Verify trends chart displays
```

---

## 🧪 INTEGRATION TESTS

### INT-1: Complete Publishing Flow
```
1. Add Telegram account
2. Test connection - SUCCESS
3. Create new post with AI
4. Select Telegram
5. Publish now
6. Verify post published
7. Check dashboard - shows published post
8. Navigate to Analytics
9. Verify post tracked
```

### INT-2: Scheduled Post Flow
```
1. Create post
2. Schedule for +1 hour
3. Verify in queue
4. Verify in calendar
5. Wait for cron (or trigger manually)
6. Verify post published
7. Verify removed from queue
```

### INT-3: Error Handling
```
1. Add account with invalid credentials
2. Try to publish
3. Verify error message displayed
4. Verify post marked as "failed"
5. Verify retry logic activates
```

---

## 📊 PERFORMANCE TESTS

### PERF-1: AI Generation Speed
- Generate content < 5 seconds
- Multiple variations < 10 seconds

### PERF-2: Publishing Speed
- Single platform < 3 seconds
- Multiple platforms (7) < 15 seconds

### PERF-3: Dashboard Load
- Dashboard page < 2 seconds
- With 1000+ posts < 3 seconds

---

## 🔒 SECURITY TESTS

### SEC-1: Credential Storage
- API keys encrypted in database
- Never displayed in HTML/JS
- Masked in API responses

### SEC-2: AJAX Nonce Validation
- All AJAX calls require valid nonce
- Expired nonces rejected
- Wrong nonces rejected

### SEC-3: Capability Checks
- Non-admins can't access settings
- Editors can create posts
- Authors limited to own posts

---

## ♿ ACCESSIBILITY TESTS

### ACC-1: Keyboard Navigation
- Tab through all form fields
- Enter to submit forms
- Escape to close modals

### ACC-2: Screen Reader
- All buttons have aria-labels
- Error messages announced
- Success messages announced

---

## 📱 RESPONSIVE TESTS

### RESP-1: Mobile View
- Dashboard cards stack vertically
- Composer usable on mobile
- Tables scroll horizontally
- Buttons touch-friendly (44px min)

### RESP-2: Tablet View
- Grid layouts adjust
- Forms remain usable
- Modals fit screen

---

## PRIORITY MATRIX

| Feature | Priority | Complexity | Status |
|---------|----------|------------|--------|
| Add Account | P0 | Medium | ❌ TODO |
| AI Generate | P0 | High | ❌ TODO |
| Publish Now | P0 | Medium | ❌ TODO |
| Schedule Post | P1 | Medium | ❌ TODO |
| Dashboard Stats | P1 | Low | ✅ DONE |
| Analytics View | P2 | Medium | ❌ TODO |
| Calendar View | P2 | High | ❌ TODO |
| Edit Account | P1 | Medium | ❌ TODO |
| Delete Account | P1 | Low | ❌ TODO |

---

**Next Steps:**
1. Implement AJAX handlers for all actions
2. Create JavaScript for frontend interactions
3. Add form validation
4. Implement modals for Add/Edit account
5. Test each user story end-to-end
