Build Admin Dashboard for Aid Registry System

Create the main index dashboard page for aid-registry system.

Objective

Build a statistics dashboard that gives management a real-time overview of:

Families

Aid distributions

Monthly totals

Office activity

Recent distributions

The page must be clean, readable, and data-focused.

🧩 Section 1 — Top Statistic Cards

Display 6 KPI cards in responsive grid:

Total Families

Count from families table

Total Aid Distributions

Count from aid_distributions

Total Cash Distributed (All Time)

Sum cash_amount where aid_mode = cash

Current Month Distributions

Count where distributed_at is current month

Current Month Cash Total

Sum cash_amount current month

Active Offices

Count offices where is_active = true

Each card should show:

Title

Large number

Small comparison text (e.g. +12% from last month)

📊 Section 2 — Monthly Chart

Add chart:

Title: Monthly Distribution Overview

Data:

Month

Total Distributions

Total Cash Amount

Chart type:
Bar chart (Distributions)
Line overlay (Cash total)

📋 Section 3 — Office Performance Table

Table columns:

Office Name

Total Distributions

Cash Total

In-kind Count

Last Distribution Date

Order by highest distributions.

📋 Section 4 — Top Aid Items (In-Kind)

Table:

Aid Item Name

Total Times Distributed

Last Distribution Date

Order descending by usage.

📋 Section 5 — Recent Distributions

Show last 10 operations:

Columns:

Date

Family Name

Office

Aid Mode

Cash / Item

Created By

Add button:
View Details

🎨 UI Rules

Clean admin style

Responsive grid

Summary first

Tables paginated

Use soft background

Highlight cash totals in green

Highlight cancelled (if status exists) in red

⚙️ Performance Rules

Use eager loading

Use aggregate queries (COUNT, SUM)

Cache dashboard data for 5 minutes

Do NOT load all distributions raw

🧱 Data Queries Required

Prepare service class:

DashboardService

Methods:

getGlobalStats()

getMonthlyStats()

getOfficeStats()

getTopAidItems()

getRecentDistributions()

💡 Important

Dashboard must be read-only.
No editing here.
Only monitoring and reporting.