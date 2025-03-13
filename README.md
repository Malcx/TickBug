# TickBug

A comprehensive bug tracking system built with PHP, jQuery, and MySQL. This application allows teams to manage projects, deliverables, and tickets in a collaborative environment.

## Features

- **User Authentication**: Secure login, registration, and password reset
- **Project Management**: Create, update, and archive projects
- **User Roles**: Owner, Project Manager, Tester, Reviewer, Developer, Designer, Viewer
- **Deliverables**: Organize work into deliverables within projects
- **Tickets**: Create, assign, and track tickets with various statuses and priorities
- **Comments**: Discuss tickets with team members
- **File Attachments**: Attach files to tickets and comments
- **Drag-and-Drop**: Easily reassign tickets and change statuses
- **Activity Logging**: Track all actions in the system
- **Email Notifications**: Get notified of important changes
- **Reporting**: Generate reports on project status, user activity, and more
- **Mobile Responsive**: Access the system from any device

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache or Nginx)
- Mailgun account for email notifications

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/yourusername/bug-tracker.git
   ```

2. Create a MySQL database:
   ```sql
   CREATE DATABASE tickbug;
   ```

3. Import the database structure:
   ```
   mysql -u username -p tickbug < database-structure.sql
   ```

4. Configure the application:
   - Rename `config/config.sample.php` to `config/config.php`
   - Update database credentials in `config/database.php`
   - Set up Mailgun API key and domain in `config/mailgun.php`

5. Set up your web server to point to the project directory.

6. Make sure the `uploads` directory is writable by the web server:
   ```
   chmod 755 uploads
   ```

7. Access the application in your browser and register an account.

## Project Structure

- **api/**: API endpoints for AJAX requests
- **assets/**: CSS, JavaScript, and image files
- **config/**: Configuration files
- **includes/**: Core PHP functionality
- **uploads/**: Uploaded files
- **views/**: HTML templates
- **index.php**: Main entry point

## User Roles

- **Owner**: Full access to the project, can add/remove users and assign any role
- **Project Manager**: Can manage deliverables, tickets, and users but cannot change owners
- **Developer**: Can create, edit, and update tickets
- **Designer**: Can create, edit, and update tickets
- **Reviewer**: Can view and comment on tickets, access to reports
- **Tester**: Can create tickets and add comments but cannot edit existing tickets
- **Viewer**: Read-only access to all project contents

## Ticket Statuses

- **New**: Recently created, not yet addressed
- **Needs clarification**: More information needed before proceeding
- **Assigned**: Assigned to a team member but not started
- **In progress**: Work has begun on this ticket
- **In review**: Work completed, awaiting review
- **Complete**: Fully completed and verified
- **Rejected**: Cannot or will not be implemented
- **Ignored**: Intentionally set aside

## Ticket Priorities

- **1-Critical**: Highest priority, requires immediate attention
- **1-Important**: High priority, should be addressed soon
- **2-Nice to have**: Medium priority, should be addressed when possible
- **3-Feature Request**: Low priority, enhancements for future consideration
- **4-Nice to have**: Lowest priority, may or may not be implemented

## Usage

### Creating a Project

1. Log in to the system
2. Click "Create New Project" button
3. Enter project name and description
4. Submit the form

### Managing Users

1. Open a project
2. Click "Manage Users" button
3. Enter email address and select role for new users
4. Click "Add User" button

### Creating Deliverables

1. Open a project
2. Click "Add Deliverable" button
3. Enter deliverable name and description
4. Submit the form

### Creating Tickets

1. Open a project
2. Click "Add Ticket" button on a deliverable
3. Enter ticket details (title, description, priority, etc.)
4. Submit the form

### Using Drag and Drop

- Drag tickets to users in the sidebar to assign
- Drag tickets to status labels to change status
- Drag tickets within a deliverable to reorder
- Drag deliverables to reorder

## Development

### Adding Custom Fields

To add custom fields to tickets:

1. Modify the `tickets` table in the database
2. Update the ticket forms in `views/tickets/create.php` and `views/tickets/edit.php`
3. Update the ticket functions in `includes/tickets.php`

### Adding New Reports

To add a new report type:

1. Create a new function in `includes/reports.php`
2. Add a new case in the switch statement in `reports.php`
3. Create a new view file in `views/reports/`

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Credits

- PHP
- jQuery
- MySQL
- jQuery UI (for drag and drop functionality)
- Mailgun (for email notifications)

## Support

For support, please open an issue on the GitHub repository.