# AI Usage Log

**Project:** EquityFlow (PHP, HTML, CSS, MySQL)
**Author:** Md Muhibul Islam Abir (ID: 242-134-042)
**Supervisor:** Lukman Nakib, Guest Lecturer, Software Engineering Department, Metropolitan University, Sylhet
**Repository:** https://github.com/MI-muhibul/EquityFlow

This file records how AI assistance was used while building EquityFlow, so the work is transparent and every AI-generated part can be traced to a commit. I am the sole developer and I am responsible for all code in this repository.

---

## 1. AI Tools Used

| Tool | Version / Model | Used for |
|---|---|---|
| Claude (Anthropic) | _fill in model name_ | Code generation, debugging, test planning, documentation drafts |
| _other tool, if any_ | | |

## 2. Rules I Followed

1. AI output is a draft. I read, ran and understood every file before committing it.
2. Every push was tested in XAMPP before it was committed.
3. No real passwords, API keys or personal data were ever pasted into an AI tool or committed.
4. Security-sensitive code (login, CSRF, SQL, sessions) was reviewed line by line against the proposal's requirements.
5. Where AI output was wrong or incomplete, I recorded the problem and the fix below.

## 3. Standard Context Prompt

This block was pasted at the start of every AI session:

```
Act as a senior PHP engineer. Project: EquityFlow, a bilingual (Bangla/English) digital Khata ledger for small shops in Bangladesh.
Stack: PHP 8.x OOP + MVC, MySQL via PDO prepared statements, HTML5, CSS3. NO JavaScript. XAMPP.
Folders: config/ models/ controllers/ views/ assets/ lang/ database/ backups/ docs/ index.php
Rules: prepared statements only; htmlspecialchars on all output; CSRF token on every POST form; money shown as ৳ with 2 decimals; every UI string comes from lang files; dark high-contrast responsive CSS; beginner-readable code with short comments.
Output: full file contents with paths, then a test checklist, then the commit message.
```

## 4. Summary Table

| # | Push | Commit message | AI used for | Date | Status |
|---|---|---|---|---|---|
| 1 | Repo skeleton, router and README | `chore: initial repo skeleton, router and README` | Folders, .gitignore, LICENSE, index.php router with page whitelist | | ☐ |
| 2 | Database schema and seed data | `feat(db): add schema and seed data` | schema.sql (8 tables, FKs, indexes), seed.sql | | ☐ |
| 3 | PDO connection, constants, secure session | `feat(config): PDO connection, constants, secure session` | Database singleton, constants, session helper, db-check page | | ☐ |
| 4 | Bangla / English language system | `feat(lang): Bangla/English files and toggle` | bn.php, en.php, t() helper, language switch | | ☐ |
| 5 | Base layout and dark theme | `feat(ui): base layout, dark theme, helpers` | Header/footer, style.css, format_money(), flash helpers | | ☐ |
| 6 | Login with password_hash | `feat(auth): login with password_hash` | User model, AuthController, login view | | ☐ |
| 7 | Lockout, timeout, CSRF, change password | `feat(auth): lockout, timeout, CSRF, change password` | 5-attempt lockout, 30-min timeout, CSRF helpers, change-password | | ☐ |
| 8 | Dashboard shell and navigation | `feat(dashboard): shell and navigation` | DashboardController, nav bar, summary cards | | ☐ |
| 9 | Customers: add and list | `feat(customer): add and list customers` | Customer model, list view, add form, phone validation | | ☐ |
| 10 | Customers: edit, view, delete | `feat(customer): edit, view, delete with balance rule` | Edit form, profile page, delete only at zero balance | | ☐ |
| 11 | Credit sales and payments | `feat(transaction): credit sale and payment with rollback safety` | Transaction model, PDO transactions, credit-limit check | | ☐ |
| 12 | History and receipt | `feat(ledger): history with running balance and receipt` | History page, running balance, on-screen receipt | | ☐ |
| 13 | Products: add, edit, stock status | `feat(product): add, edit, stock status` | Product model, status badges, stock update | | ☐ |
| 14 | Profit, margin and SVG sparkline | `feat(product): profit, margin, SVG sparkline` | Profit per unit, margin %, sparkline helper | | ☐ |
| 15 | Stock reduction and low-stock alerts | `feat(inventory): stock reduction and low-stock alerts` | sale_items, stock deduction, dashboard low-stock panel | | ☐ |
| 16 | Search and filters | `feat(search): filters and pagination` | GET filters, pagination, 10,000-row performance test | | ☐ |
| 17 | Monthly reports | `feat(report): monthly summary and outstanding list` | Report model, monthly summary, outstanding list | | ☐ |
| 18 | Settings and SQL backup | `feat(settings): policies and SQL backup` | Settings page, PDO-based SQL dump, protected backups/ | | ☐ |
| 19 | Testing and bug fixes | `test: security, performance and regression fixes` | Full test plan, test report, bug fixes | | ☐ |
| 20 | Final documentation and v1.0 release | `docs: final documentation and v1.0 release` | README final, user guide, changelog, v1.0 tag | | ☐ |

Status key: ☐ not started, ◐ in progress, ☑ committed and tested.

---

## 5. Detailed Log

### Push 1: Repo skeleton, router and README

- **Date:** 
- **Commit:** `chore: initial repo skeleton, router and README`
- **AI tool / model:** 
- **Task given to AI:** Folders, .gitignore, LICENSE, index.php router with page whitelist
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-01.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 2: Database schema and seed data

- **Date:** 
- **Commit:** `feat(db): add schema and seed data`
- **AI tool / model:** 
- **Task given to AI:** schema.sql (8 tables, FKs, indexes), seed.sql
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-02.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 3: PDO connection, constants, secure session

- **Date:** 
- **Commit:** `feat(config): PDO connection, constants, secure session`
- **AI tool / model:** 
- **Task given to AI:** Database singleton, constants, session helper, db-check page
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-03.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 4: Bangla / English language system

- **Date:** 
- **Commit:** `feat(lang): Bangla/English files and toggle`
- **AI tool / model:** 
- **Task given to AI:** bn.php, en.php, t() helper, language switch
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-04.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 5: Base layout and dark theme

- **Date:** 
- **Commit:** `feat(ui): base layout, dark theme, helpers`
- **AI tool / model:** 
- **Task given to AI:** Header/footer, style.css, format_money(), flash helpers
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-05.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 6: Login with password_hash

- **Date:** 
- **Commit:** `feat(auth): login with password_hash`
- **AI tool / model:** 
- **Task given to AI:** User model, AuthController, login view
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-06.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 7: Lockout, timeout, CSRF, change password

- **Date:** 
- **Commit:** `feat(auth): lockout, timeout, CSRF, change password`
- **AI tool / model:** 
- **Task given to AI:** 5-attempt lockout, 30-min timeout, CSRF helpers, change-password
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-07.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 8: Dashboard shell and navigation

- **Date:** 
- **Commit:** `feat(dashboard): shell and navigation`
- **AI tool / model:** 
- **Task given to AI:** DashboardController, nav bar, summary cards
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-08.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 9: Customers: add and list

- **Date:** 
- **Commit:** `feat(customer): add and list customers`
- **AI tool / model:** 
- **Task given to AI:** Customer model, list view, add form, phone validation
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-09.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 10: Customers: edit, view, delete

- **Date:** 
- **Commit:** `feat(customer): edit, view, delete with balance rule`
- **AI tool / model:** 
- **Task given to AI:** Edit form, profile page, delete only at zero balance
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-10.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 11: Credit sales and payments

- **Date:** 
- **Commit:** `feat(transaction): credit sale and payment with rollback safety`
- **AI tool / model:** 
- **Task given to AI:** Transaction model, PDO transactions, credit-limit check
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-11.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 12: History and receipt

- **Date:** 
- **Commit:** `feat(ledger): history with running balance and receipt`
- **AI tool / model:** 
- **Task given to AI:** History page, running balance, on-screen receipt
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-12.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 13: Products: add, edit, stock status

- **Date:** 
- **Commit:** `feat(product): add, edit, stock status`
- **AI tool / model:** 
- **Task given to AI:** Product model, status badges, stock update
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-13.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 14: Profit, margin and SVG sparkline

- **Date:** 
- **Commit:** `feat(product): profit, margin, SVG sparkline`
- **AI tool / model:** 
- **Task given to AI:** Profit per unit, margin %, sparkline helper
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-14.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 15: Stock reduction and low-stock alerts

- **Date:** 
- **Commit:** `feat(inventory): stock reduction and low-stock alerts`
- **AI tool / model:** 
- **Task given to AI:** sale_items, stock deduction, dashboard low-stock panel
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-15.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 16: Search and filters

- **Date:** 
- **Commit:** `feat(search): filters and pagination`
- **AI tool / model:** 
- **Task given to AI:** GET filters, pagination, 10,000-row performance test
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-16.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 17: Monthly reports

- **Date:** 
- **Commit:** `feat(report): monthly summary and outstanding list`
- **AI tool / model:** 
- **Task given to AI:** Report model, monthly summary, outstanding list
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-17.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 18: Settings and SQL backup

- **Date:** 
- **Commit:** `feat(settings): policies and SQL backup`
- **AI tool / model:** 
- **Task given to AI:** Settings page, PDO-based SQL dump, protected backups/
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-18.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 19: Testing and bug fixes

- **Date:** 
- **Commit:** `test: security, performance and regression fixes`
- **AI tool / model:** 
- **Task given to AI:** Full test plan, test report, bug fixes
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-19.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

### Push 20: Final documentation and v1.0 release

- **Date:** 
- **Commit:** `docs: final documentation and v1.0 release`
- **AI tool / model:** 
- **Task given to AI:** README final, user guide, changelog, v1.0 tag
- **Prompt used:** _paste the exact prompt, or write "see docs/prompts/push-20.md"_
- **What the AI produced:** 
- **What I changed or rewrote:** 
- **Problems found in AI output:** 
- **How I tested it:** 
- **Result:** ☐ passed ☐ passed after fixes ☐ failed (explain)
- **What I learned:** 

---

## 6. Bug and Correction Register

Use this table whenever AI output was wrong, insecure or did not match the proposal.

| Push | Problem in AI output | How I found it | Fix applied |
|---|---|---|---|
| | | | |

## 7. Self-Written vs AI-Assisted Parts

| Area | Mostly my own work | AI-assisted | Notes |
|---|---|---|---|
| Database design decisions | | | |
| Business rules (credit limit, deletion rule) | | | |
| PHP model and controller code | | | |
| CSS / UI design | | | |
| Bangla translations | | | Reviewed by me as a native speaker |
| Test cases | | | |
| Documentation | | | |

## 8. Declaration

I confirm that I have reviewed, tested and understood the code in this repository, and that the entries above are an honest record of how AI tools were used.

**Signed:** Md Muhibul Islam Abir
**Date:** 
