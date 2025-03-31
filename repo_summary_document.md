# TickBug - Bug Tracker Repository Summary

TickBug is a PHP-based bug tracking and project management application designed with a clean, modular architecture following an MVC-like structure. The application allows users to manage projects, create deliverables, track tickets, and collaborate with team members.

## Core Architecture

The application follows a simple but effective structure:

- **Front Controller Pattern**: All requests go through specific entry point files (e.g., `projects.php`, `tickets.php`)
- **API Endpoints**: AJAX-based functionality handled via `/api/` directory endpoints
- **Helper Functions**: Core business logic stored in modular `/includes/` files
- **Views**: Presentation logic in `/views/` directory with template partials
- **Assets**: CSS/JS organized by feature in `/assets/` directory

## Database Schema

The database uses MySQL with the following key tables:

- `users`: User accounts and authentication
- `projects`: Projects with metadata like name, description, and theme color
- `project_users`: Many-to-many relationship between users and projects with roles
- `deliverables`: Project deliverables (features/components)
- `tickets`: Individual tickets (bugs/issues) associated with deliverables
- `comments`: Comments on tickets
- `files`: Uploaded files that can be attached to tickets or comments
- `activities`: Audit log of all user actions
- `statuses`: Predefined ticket statuses
- `priorities`: Predefined ticket priorities

## Directory Structure
/
├── api/                # API endpoints for AJAX requests
│   ├── auth/           # Authentication endpoints
│   ├── comments/       # Comment CRUD operations
│   ├── deliverables/   # Deliverable CRUD operations
│   ├── files/          # File upload/management
│   ├── projects/       # Project CRUD & user management
│   └── tickets/        # Ticket CRUD operations
├── assets/             # Frontend assets
│   ├── css/            # Stylesheets
│   └── js/             # JavaScript files
├── config/             # Configuration files
├── includes/           # Core business logic
├── uploads/            # User uploaded files
└── views/              # View templates
├── auth/           # Authentication views
├── deliverables/   # Deliverable views
├── errors/         # Error pages
├── includes/       # Reusable view components
├── projects/       # Project views
├── reports/        # Reporting views
├── tickets/        # Ticket views
└── users/          # User profile views


## Key Files and Components

### Entry Points
- `index.php` - Main entry point, redirects to login or projects
- `login.php`, `register.php`, `forgot-password.php` - Auth pages
- `projects.php` - Project listing and management
- `deliverables.php` - Deliverable management
- `tickets.php` - Ticket management
- `reports.php` - Reporting functionality
- `profile.php` - User profile management
- `uploads.php` - File download handler

### Core Includes (Business Logic)
- `includes/helpers.php` - Central include file and utility functions
- `includes/auth.php` - Authentication logic
- `includes/projects.php` - Project management functions
- `includes/deliverables.php` - Deliverable management functions
- `includes/tickets.php` - Ticket management functions
- `includes/comments.php` - Comment functionality
- `includes/files.php` - File upload/download logic
- `includes/users.php` - User management
- `includes/activity.php` - Activity logging and notifications
- `includes/reports.php` - Reporting and analytics

### Configuration
- `config/config.php` - Main configuration settings
- `config/database.php` - Database connection settings
- `config/mailgun.php` - Email notification settings

### Frontend Assets
- `assets/css/style.css` - Main styles
- `assets/css/vars.css` - CSS variables for theming
- `assets/js/config.js` - Frontend configuration
- Feature-specific JS files:
  - `assets/js/auth.js`
  - `assets/js/projects.js`
  - `assets/js/tickets.js`
  - etc.

## Application Flow

1. **Authentication Flow**:
   - Users register/login through auth pages
   - Sessions managed through PHP session handling
   - Password reset via email tokens

2. **Project Management Flow**:
   - Users create/view/edit projects
   - Project owners can add users with specific roles
   - Project settings include theme color and archiving

3. **Ticket Lifecycle**:
   - Projects contain deliverables
   - Deliverables contain tickets
   - Tickets have statuses (New → Needs clarification → Assigned → In progress → In review → Complete/Rejected/Ignored)
   - Tickets have priorities (Critical → Important → Nice to have → etc.)
   - Tickets can be assigned to users
   - Users can comment on tickets and attach files

4. **Notification System**:
   - Activities logged for audit trail
   - Email notifications for key events
   - User-configurable notification preferences

## Key Features

1. **User Management**:
   - Registration, authentication, password reset
   - User profiles and notification preferences

2. **Project Structure**:
   - Project → Deliverables → Tickets hierarchy
   - Role-based permissions (Owner, Project Manager, Developer, Designer, Tester, Reviewer, Viewer)
   - Project archiving

3. **Ticket Management**:
   - Rich ticket details (title, description, URL, files)
   - Status and priority tracking
   - User assignment
   - Comments and file attachments
   - Activity history

4. **UI/UX Features**:
   - Drag-and-drop ticket prioritization
   - Custom theming per project
   - Responsive design
   - Filter and search functionality

5. **Reporting**:
   - Project status reporting
   - User activity reports
   - Ticket status distribution
   - Team productivity metrics

## Code Organization Patterns

1. **Authentication & Authorization**:
   - Session-based authentication
   - Role-based access control per project
   - Function-level permission checks

2. **Database Access**:
   - Prepared statements for security
   - Connection pooling
   - Transaction support for data integrity

3. **Error Handling**:
   - Custom error pages
   - Form validation with user feedback
   - Exception handling with logging

4. **Frontend Interaction**:
   - AJAX-based form submissions
   - jQuery UI for drag-and-drop
   - Dynamic UI updates
   - Modal dialogs

5. **Security Measures**:
   - Password hashing (bcrypt)
   - CSRF protection
   - XSS prevention (input sanitization, output escaping)
   - File upload validation

## API Endpoints

The API endpoints follow RESTful-like patterns:

- **Authentication**:
  - `/api/auth/login.php` - User login
  - `/api/auth/register.php` - User registration
  - `/api/auth/reset-password.php` - Password reset

- **Projects**:
  - `/api/projects/create.php` - Create project
  - `/api/projects/update.php` - Update project
  - `/api/projects/delete.php` - Delete project
  - `/api/projects/archive.php` - Archive project
  - `/api/projects/users.php` - Manage project users

- **Deliverables**:
  - `/api/deliverables/create.php` - Create deliverable
  - `/api/deliverables/update.php` - Update deliverable
  - `/api/deliverables/delete.php` - Delete deliverable
  - `/api/deliverables/reorder.php` - Reorder deliverables

- **Tickets**:
  - `/api/tickets/create.php` - Create ticket
  - `/api/tickets/update.php` - Update ticket
  - `/api/tickets/delete.php` - Delete ticket
  - `/api/tickets/assign.php` - Assign ticket
  - `/api/tickets/change-status.php` - Change ticket status
  - `/api/tickets/reorder.php` - Reorder tickets

- **Comments**:
  - `/api/comments/create.php` - Add comment
  - `/api/comments/update.php` - Update comment
  - `/api/comments/delete.php` - Delete comment

- **Files**:
  - `/api/files/upload.php` - Upload files
  - `/api/files/delete.php` - Delete files

## Dependencies

The application uses minimal external dependencies:

1. **Frontend Libraries** (loaded via CDN):
   - jQuery - Core functionality and DOM manipulation
   - jQuery UI - Drag-and-drop and sortable lists
   - Chart.js - Data visualization for reports

2. **Backend Libraries**:
   - PHP 7.4+ - Core language
   - MySQL/MariaDB - Database

3. **Server Requirements**:
   - Apache with mod_rewrite or Nginx
   - PHP 7.4+ with required extensions
   - MySQL/MariaDB database

## Security Considerations

1. **Authentication Security**:
   - Password hashing with bcrypt
   - Secure session management
   - Token-based password reset

2. **Input/Output Security**:
   - Prepared statements for all SQL
   - Input validation for all form data
   - Output escaping with htmlspecialchars()

3. **File Security**:
   - MIME type validation
   - File size restrictions
   - Unique renamed filenames
   - Restricted file extensions

4. **Access Control**:
   - Function-level permission checks
   - Project-based role checks
   - Resource ownership validation

## Configuration

Configuration is managed through:

- `config/config.php` - Main application settings (paths, debug mode, etc.)
- `config/database.php` - Database connection settings
- `config/mailgun.php` - Email notification settings

## Deployment Considerations

1. **Initial Setup**:
   - Copy sample config files (`*_sample.php`) to actual config files
   - Set correct paths in configuration
   - Create and initialize database using `database.sql`
   - Set proper permissions on uploads directory

2. **Security Recommendations**:
   - Run behind HTTPS
   - Set secure cookie flags in production
   - Disable error display in production
   - Use environment variables for sensitive settings

3. **Performance Optimization**:
   - Enable opcache for PHP
   - Database indexing (already in schema)
   - Consider caching for report generation