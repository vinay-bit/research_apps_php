# Publication Workflow System

## Overview
The Publication Workflow System provides a complete pipeline for managing research publications from initial preparation through venue submissions and acceptance tracking.

## Workflow Stages

### 1. Ready for Publication
- **Purpose**: Prepare publications and ensure all requirements are met
- **Requirements for Approval**: 
  - Paper Title
  - First Draft Link (required)
  - AI Detection Link (required) 
  - Plagiarism Report Link (optional but recommended)
  - Mentor Affiliation
- **Status Validation**: Publications can only be marked as "approved" if both Paper Link and AI Detection Link are present

### 2. In Publication
- **Purpose**: Manage submissions to conferences and journals
- **Entry Criteria**: 
  - Status must be "approved" in Ready for Publication
  - Both Paper Link and AI Detection Link must be provided
- **Capabilities**:
  - Apply to multiple conferences
  - Apply to multiple journals
  - Track application status (Applied, Under Review, Accepted, Rejected, Withdrawn)
  - Store feedback and response dates
  - Manage submission links and manuscript IDs

## Database Schema

### Core Tables

#### `in_publication`
- Stores approved publications ready for venue submission
- Links to original `ready_for_publication` entry
- Includes all document links (first draft, plagiarism report, AI detection, final paper)

#### `publication_conference_applications`
- Tracks applications to conferences
- Stores submission deadlines, links, and status updates
- Links to both publication and conference

#### `publication_journal_applications`
- Tracks applications to journals
- Includes manuscript ID tracking
- Stores publisher feedback and review status

#### `in_publication_students`
- Copies student author information from ready_for_publication_students
- Maintains author order and affiliation details

### Status Management
- **Workflow Status**: Added to `ready_for_publication` table
  - `active`: Still in ready for publication workflow
  - `moved_to_publication`: Moved to in-publication workflow
- **Application Status**: For venue applications
  - `applied`: Initial application submitted
  - `under_review`: Currently being reviewed
  - `accepted`: Application accepted
  - `rejected`: Application rejected
  - `withdrawn`: Application withdrawn

## User Interface

### Ready for Publication Interface (`/publications/ready_for_publication.php`)
**Enhanced Features**:
- ✅ AI Detection Link field added (required for approval)
- ✅ Validation prevents approval without required links
- ✅ "Move to In Publication" action for approved items
- ✅ Form validation with error messaging

### In Publication Interface (`/publications/in_publication.php`)
**New Features**:
- 📊 Statistics dashboard showing applications by status
- 📝 Publication cards with all document links
- 🎯 Apply to conferences and journals
- 📈 Track application status updates
- 💬 Store and view feedback from venues
- 🔍 Search and filter publications

### Navigation
- Added "In Publication" menu item in sidebar
- Breadcrumb navigation between workflow stages

## Workflow Process

### Step 1: Prepare Publication
1. Create publication in "Ready for Publication"
2. Add paper title, mentor details, and notes
3. Upload required documents:
   - First Draft Link (**required**)
   - AI Detection Report Link (**required**)
   - Plagiarism Report Link (recommended)

### Step 2: Approve for Publication
1. Set status to "Approved" (only possible with required links)
2. System validates presence of Paper Link and AI Detection Link
3. "Move to In Publication" action becomes available

### Step 3: Move to In Publication
1. Click "Move to In Publication" from actions menu
2. System automatically:
   - Creates entry in `in_publication` table
   - Copies all publication data and documents
   - Copies student author information
   - Updates workflow status to prevent duplicates

### Step 4: Apply to Venues
1. Navigate to "In Publication" section
2. Use "Apply to Venue" dropdown for each publication
3. **Conference Applications**:
   - Select conference from dropdown
   - Set application and deadline dates
   - Add submission link and notes
4. **Journal Applications**:
   - Select journal from dropdown
   - Set application and deadline dates
   - Add manuscript ID and submission link
   - Add notes

### Step 5: Track Applications
1. View all applications in publication cards
2. Update status as venues respond:
   - Applied → Under Review → Accepted/Rejected
   - Add response dates and feedback
3. Monitor statistics dashboard for overview

## Features

### Validation & Error Prevention
- ✅ Prevents approval without required documents
- ✅ Prevents duplicate moves to in-publication
- ✅ Validates application data before submission
- ✅ Clear error messaging for missing requirements

### Document Management
- 🔗 First Draft Link (required for approval)
- 🔗 AI Detection Report Link (required for approval)  
- 🔗 Plagiarism Report Link (optional)
- 🔗 Final Paper Link (can be added in in-publication stage)
- 📄 All links displayed with appropriate icons and labels

### Application Tracking
- 📅 Application and deadline date tracking
- 🔗 Submission system links
- 📝 Manuscript ID tracking for journals
- 💬 Feedback storage from venues
- 📊 Status progression tracking
- 📈 Statistics and reporting

### Author Management
- 👥 Student author information automatically copied
- 🏫 Affiliation tracking for each author
- 📧 Contact information maintained
- 🔢 Author order preservation

## Database Setup

### Required SQL Scripts
1. `create_publication_workflow_tables.sql` - Creates all new tables and adds required fields
2. `remove_target_venue_fields.sql` - (Optional) If removing old venue fields

### Migration Steps
1. **Run workflow setup**: Execute `create_publication_workflow_tables.sql`
2. **Verify tables**: Check that all new tables were created
3. **Test workflow**: Create test publication and move through workflow
4. **Update existing data**: Optionally migrate existing approved publications

## File Structure
```
publications/
├── ready_for_publication.php (enhanced)
├── edit_ready_publication.php (enhanced)  
├── in_publication.php (new)
├── conferences.php (existing)
└── journals.php (existing)

classes/
├── ReadyForPublication.php (enhanced)
├── InPublication.php (new)
├── Conference.php (existing)
└── Journal.php (existing)
```

## Benefits

### For Researchers
- **Clear Workflow**: Step-by-step process from preparation to submission
- **Requirement Validation**: Cannot proceed without necessary documents
- **Comprehensive Tracking**: Full visibility into application status
- **Document Organization**: All links and files in one place

### For Administrators
- **Quality Control**: Ensures standards before publications advance
- **Progress Monitoring**: Real-time status of all submissions
- **Statistics**: Overview of publication pipeline health
- **Audit Trail**: Complete history of applications and responses

### For Mentors
- **Student Oversight**: Track student publication progress
- **Venue Strategy**: See which venues are being targeted
- **Success Metrics**: Monitor acceptance rates and feedback
- **Resource Planning**: Understand submission timelines

## Technical Specifications

### Security
- ✅ Session-based authentication
- ✅ Permission-based actions (admin can delete)
- ✅ Input validation and sanitization
- ✅ SQL injection prevention via prepared statements

### Performance
- ✅ Indexed database fields for fast queries
- ✅ Efficient joins between related tables
- ✅ Pagination-ready structure
- ✅ Optimized for large datasets

### Scalability
- ✅ Modular class structure
- ✅ Separate concerns (publications vs applications)
- ✅ Extensible status system
- ✅ Future-proof database design

## Troubleshooting

### Common Issues
1. **Cannot approve publication**: Ensure both Paper Link and AI Detection Link are provided
2. **Move to Publication not available**: Check that status is "approved" and required links exist
3. **Duplicate error when moving**: Publication may already be in in-publication workflow
4. **Missing applications**: Verify conference/journal management systems are populated

### Error Messages
- `"Both paper link and AI detection link are required to set status as approved"`
- `"Both paper link and AI detection link are required to move to in-publication"`
- `"This publication has already been moved to in-publication"`
- `"Publication not found or not approved"`

This comprehensive workflow system ensures quality control while providing powerful tracking and management capabilities for the entire publication process. 