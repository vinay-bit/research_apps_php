# Conference & Journal Management Setup Instructions

The conference and journal management functionality has been added to your publications system. To complete the setup, you need to run the following SQL scripts in your database:

## 1. Create Conferences Table

Run the following SQL script in your MySQL database (via phpMyAdmin or your preferred MySQL client):

**File:** `create_conferences_table.sql`

This will:
- Create the `conferences` table with all required fields
- Add sample conference data
- Set up proper indexes and foreign keys

## 2. Create Journals Table

Run the following SQL script in your MySQL database:

**File:** `create_journals_table.sql`

This will:
- Create the `journals` table with all required fields
- Add sample journal data
- Set up proper indexes and foreign keys

## 3. Add Conference Support to Publications

Run the following SQL script to link publications with conferences:

**File:** `add_conference_to_publications.sql`

This will:
- Add `conference_id` column to the `ready_for_publication` table
- Create the foreign key relationship between publications and conferences
- Add proper indexing

## 4. Add Journal Support to Publications

Run the following SQL script to link publications with journals:

**File:** `add_journal_to_publications.sql`

This will:
- Add `journal_id` column to the `ready_for_publication` table
- Create the foreign key relationship between publications and journals
- Add proper indexing

## 5. Access the Conference & Journal Management

Once the database is set up, you can:

1. **Access Conference Management:**
   - Go to "Research & Publications" → "Conference Management" in the sidebar
   - Add, edit, and manage conferences

2. **Access Journal Management:**
   - Go to "Research & Publications" → "Journal Management" in the sidebar
   - Add, edit, and manage journals

3. **Link Publications to Conferences & Journals:**
   - When creating or editing publications, you can now select a target conference and/or journal
   - The conference and journal information will be displayed in the publications list

## Features Added:

### Conference Management Page (`/publications/conferences.php`)
- ✅ Add new conferences with all required fields
- ✅ Conference name, shortform, link, affiliation, type, and date
- ✅ Dropdown for affiliations (IEEE, Springer, ACM, Elsevier, etc.)
- ✅ National/International conference type selection
- ✅ Filter conferences by affiliation, type, and search
- ✅ Statistics dashboard showing total, upcoming, international, and national conferences
- ✅ Edit and delete conferences (admin only)

### Journal Management Page (`/publications/journals.php`)
- ✅ Add new journals with all required fields
- ✅ Journal name, publisher, link, and acceptance frequency
- ✅ Dropdown for publishers (IEEE, Springer, Elsevier, ACM, etc.)
- ✅ Acceptance frequency options (Rolling, Monthly, Quarterly, Semi-annually, Yearly)
- ✅ Filter journals by publisher, acceptance frequency, and search
- ✅ Statistics dashboard showing total journals by publisher and acceptance frequency
- ✅ Edit and delete journals (admin only)

### Publications Integration
- ✅ Conference selection dropdown in "Add New Publication" form
- ✅ Journal selection dropdown in "Add New Publication" form
- ✅ Conference and journal information displayed in publications table
- ✅ Link to conference and journal management from publication forms
- ✅ Conference details shown with badges for affiliation and type
- ✅ Journal details shown with badges for publisher and acceptance frequency

### Database Structure
- ✅ `conferences` table with all required fields
- ✅ `journals` table with all required fields
- ✅ Proper foreign key relationships for both conferences and journals
- ✅ Optimized indexes for performance
- ✅ Sample data for testing

## Navigation
The "Conference Management" and "Journal Management" options have been added to the sidebar under "Research & Publications".

## Next Steps
1. Run the SQL scripts mentioned above
2. Access the conference management page to add your conferences
3. Access the journal management page to add your journals
4. Start linking your publications to relevant conferences and journals

All conference and journal functionality is now ready to use once the database setup is complete! 

# Database Setup Instructions

This document provides step-by-step instructions for setting up the complete database schema for the Research Apps platform, including conferences and journals management.

## Prerequisites

- XAMPP installed and running
- MySQL/phpMyAdmin accessible
- Database name: `research_apps_db`

## Setup Order

### Step 1: Create Base Tables (if not already done)
Run these scripts in phpMyAdmin in this exact order:

1. **Create conferences table:**
   ```sql
   -- Run: create_conferences_table.sql
   ```

2. **Create journals table:**
   ```sql
   -- Run: create_journals_table.sql
   ```

### Step 2: Update Publications Table (SAFE METHOD)
**RECOMMENDED:** Use the safe script that checks for existing columns:

```sql
-- Run: safe_add_conference_journal_columns.sql
```

This script will:
- Check if `conference_id` column exists before adding it
- Check if `journal_id` column exists before adding it  
- Check if foreign key constraints exist before adding them
- Add indexes for better performance
- Show messages indicating what was added or already existed

### Alternative Step 2: Manual Method (if needed)
If you prefer to add columns manually or need to troubleshoot:

3. **Add conference support to publications:**
   ```sql
   -- Run: add_conference_to_publications.sql
   -- (Skip if you get "Duplicate column" error)
   ```

4. **Add journal support to publications:**
   ```sql
   -- Run: add_journal_to_publications.sql
   ```

## Troubleshooting

### Error: "Duplicate column name 'conference_id'"
This means the column already exists. You have two options:

**Option A: Use the safe script (recommended)**
- Run `safe_add_conference_journal_columns.sql` instead
- This will safely add only missing columns and constraints

**Option B: Skip the problematic script**
- If `conference_id` already exists, skip `add_conference_to_publications.sql`
- Only run `add_journal_to_publications.sql`

### Error: "Duplicate column name 'journal_id'"
- The column already exists, you can skip the journal setup script

### Error: "Foreign key constraint already exists"
- The constraint is already in place, safe to ignore

## Verification

After running the setup, verify your `ready_for_publication` table has these columns:
- `id` (primary key)
- `project_id` (foreign key)
- `conference_id` (foreign key, nullable)
- `journal_id` (foreign key, nullable)
- `title`
- `authors`
- `abstract`
- `keywords`
- `status`
- `notes`
- `created_at`
- `updated_at`

## Sample Data

The setup scripts include sample data for:
- **Conferences:** IEEE conferences, ACM conferences, Springer conferences
- **Journals:** IEEE Transactions, Nature journals, Science journals, etc.

## Files Overview

### Required SQL Scripts:
1. `create_conferences_table.sql` - Creates conferences table with sample data
2. `create_journals_table.sql` - Creates journals table with sample data
3. `safe_add_conference_journal_columns.sql` - **RECOMMENDED** - Safely adds columns to publications table
4. `add_conference_to_publications.sql` - Adds conference_id to publications (manual method)
5. `add_journal_to_publications.sql` - Adds journal_id to publications (manual method)

### Optional Scripts:
- `remove_publications_tables.sql` - Removes all publication-related tables (for cleanup)

## Post-Setup

After database setup is complete:
1. Clear your browser cache
2. Navigate to the publications section
3. You should see both Conference Management and Journal Management in the sidebar
4. Test creating a new publication with conference and journal selection

## Need Help?

If you encounter issues:
1. Check the table structure in phpMyAdmin
2. Verify foreign key constraints are in place
3. Ensure sample data was inserted correctly
4. Check PHP error logs for any application-level issues 