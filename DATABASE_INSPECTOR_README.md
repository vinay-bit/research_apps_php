# Database Inspector Scripts

Two comprehensive PHP scripts to analyze and inspect your database schema, including all tables, columns, data types, and relationships.

## Files Created

1. **`database_inspector.php`** - Web-based inspector with HTML interface
2. **`database_inspector_cli.php`** - Command-line interface for terminal use

## Features

✅ **Complete Database Analysis**
- All table names and metadata
- Column details (name, type, nullable, defaults, keys)
- Primary and foreign key relationships
- Index information
- Table statistics (row count, data length, engine)

✅ **Relationship Mapping**
- Outgoing relationships (foreign keys from this table)
- Incoming relationships (other tables referencing this table)
- Complete constraint details (ON UPDATE/DELETE rules)

✅ **Multiple Output Formats**
- Beautiful HTML interface
- JSON format for APIs
- PHP array for debugging
- Command-line formatted output

## Usage

### Web Interface

#### 1. Basic HTML View (Default)
```
http://your-domain/database_inspector.php
```
- Shows a beautiful, formatted web interface
- Color-coded primary keys (yellow) and foreign keys (blue)
- Complete table relationships with visual indicators
- Responsive design that works on all devices

#### 2. JSON Output
```
http://your-domain/database_inspector.php?format=json
```
- Machine-readable JSON format
- Perfect for APIs or programmatic use
- Includes complete schema information

#### 3. PHP Array Output
```
http://your-domain/database_inspector.php?format=array
```
- Raw PHP array structure
- Useful for debugging or development

### Command Line Interface

#### 1. Database Summary (Default)
```bash
php database_inspector_cli.php
# or
php database_inspector_cli.php summary
```
Shows:
- Database name
- Total table count
- Total foreign key relationships
- List of all tables with row counts and engines

#### 2. Detailed Tables View
```bash
php database_inspector_cli.php tables
```
Shows:
- All tables with complete column information
- Data types, nullability, keys, defaults
- Foreign key references inline
- Formatted for easy reading

#### 3. Relationships Only
```bash
php database_inspector_cli.php relationships
```
Shows:
- All foreign key relationships
- Source and target tables/columns
- Update and delete rules
- Tabular format

#### 4. JSON Export
```bash
php database_inspector_cli.php json
```
- Same JSON output as web version
- Can be piped to files or other tools

## What Information You'll Get

### Table Information
- Table name and type
- Storage engine (InnoDB, MyISAM, etc.)
- Row count and data size
- Table comments

### Column Details
- Column name and data type
- Nullability (YES/NO)
- Key type (PRI, UNI, MUL)
- Default values
- Auto-increment and other extras
- Column comments

### Relationships
- **References**: Foreign keys pointing to other tables
- **Referenced By**: Other tables pointing to this table
- Constraint names
- ON UPDATE/DELETE rules (CASCADE, SET NULL, RESTRICT)

### Index Information
- Index names and types
- Columns included in each index
- Unique vs non-unique indexes

## Sample Output

### Web Interface Features
- 📋 **Table sections** with expandable information
- 🔗 **Visual relationship indicators**
- 🎨 **Color coding** for different key types
- 📊 **Summary statistics** at the top
- 🔍 **Complete schema overview**

### Command Line Features
- **Clean, formatted output** for terminal viewing
- **Tabular data** aligned for easy reading
- **Summary information** with quick statistics
- **Focused views** (just relationships, just tables, etc.)

## Database Schema Analyzed

Based on your current database, this will analyze:

**Core Tables:**
- `users`, `students`, `organizations`, `departments`
- `projects`, `project_statuses`, `subjects`, `project_tags`
- `ready_for_publication`, `in_publication`
- `conferences`, `journals`
- `publication_conference_applications`, `publication_journal_applications`

**Key Relationships:**
- User → Student assignments
- Project → Student assignments  
- Publication workflow chains
- Conference/Journal applications
- And many more...

## Error Handling

Both scripts include comprehensive error handling:
- Database connection errors
- Missing tables or columns
- Permission issues
- Invalid parameters

## Requirements

- PHP 7.0+
- PDO MySQL extension
- Access to your database
- Proper database configuration in `config/database.php`

## Security Notes

- These scripts use prepared statements to prevent SQL injection
- Only read operations are performed (no data modification)
- Uses existing database connection configuration
- No sensitive data is exposed in output

## Tips

1. **Save output to file:**
   ```bash
   php database_inspector_cli.php json > schema.json
   ```

2. **Filter specific information:**
   ```bash
   php database_inspector_cli.php relationships | grep "users"
   ```

3. **Use in documentation:**
   - HTML output can be saved and shared with team
   - JSON output can be used to generate ERD diagrams
   - Command line output works great in README files

These scripts will give you complete visibility into your database structure and relationships! 