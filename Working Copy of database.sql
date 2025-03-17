-- TickBug Database Structure
-- Use UTF8mb4_bin

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    password VARCHAR(191) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    reset_token VARCHAR(191) NULL,
    reset_token_expiry DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Projects table
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    archived BOOLEAN NOT NULL DEFAULT FALSE,
    theme_color varchar(7) DEFAULT '#201E5B'
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- User roles enum: Owner, Project Manager, Tester, Reviewer, Developer, Designer, Viewer
CREATE TABLE project_users (
    project_user_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('Owner', 'Project Manager', 'Tester', 'Reviewer', 'Developer', 'Designer', 'Viewer') NOT NULL,
    notification_preferences JSON, -- Store user notification preferences for this project
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_project_user (project_id, user_id) -- Each user can only have one role per project
);

-- Deliverables table
CREATE TABLE deliverables (
    deliverable_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    name VARCHAR(191) NOT NULL,
    description TEXT,
    display_order INT NOT NULL DEFAULT 0,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Tickets table
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    deliverable_id INT NOT NULL,
    title VARCHAR(191) NOT NULL,
    description TEXT,
    url VARCHAR(191),
    status ENUM('New', 'Needs clarification', 'Assigned', 'In progress', 'In review', 'Complete', 'Rejected', 'Ignored') NOT NULL DEFAULT 'New',
    priority ENUM('1-Critical', '1-Important', '2-Nice to have', '3-Feature Request', '4-Nice to have') NOT NULL,
    assigned_to INT NULL,
    created_by INT NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (deliverable_id) REFERENCES deliverables(deliverable_id),
    FOREIGN KEY (assigned_to) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- Comments table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    description TEXT,
    url VARCHAR(191),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Files table for attachments
CREATE TABLE files (
    file_id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(191) NOT NULL,
    filepath VARCHAR(191) NOT NULL,
    filesize INT NOT NULL,
    filetype VARCHAR(50) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id)
);

-- Files can be associated with either tickets or comments
CREATE TABLE ticket_files (
    ticket_file_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    file_id INT NOT NULL,
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES files(file_id) ON DELETE CASCADE
);

CREATE TABLE comment_files (
    comment_file_id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    file_id INT NOT NULL,
    FOREIGN KEY (comment_id) REFERENCES comments(comment_id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES files(file_id) ON DELETE CASCADE
);

-- Activity Log table
CREATE TABLE activity_log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    project_id INT NOT NULL,
    target_type ENUM('project', 'deliverable', 'ticket', 'comment', 'file', 'user') NOT NULL,
    target_id INT NOT NULL, -- ID of the item being acted upon
    action VARCHAR(191) NOT NULL, -- e.g., "created", "updated", "deleted", "status_changed", etc.
    details JSON, -- Store additional details about the action
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id)
);

-- Create ticket_statuses table
CREATE TABLE ticket_statuses (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    display_order INT NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL, -- Store color for styling
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert default statuses
INSERT INTO ticket_statuses (name, description, display_order, color) VALUES
('New', 'Ticket has been created but not yet addressed', 1, '#17a2b8'),
('Needs clarification', 'More information is needed before work can begin', 2, '#ffc107'),
('Assigned', 'Ticket has been assigned but work has not started', 3, '#6f42c1'),
('In progress', 'Work is currently in progress', 4, '#007bff'),
('In review', 'Work is complete and awaiting review', 5, '#fd7e14'),
('Complete', 'Ticket has been completed and verified', 6, '#28a745'),
('Rejected', 'Ticket has been rejected and will not be implemented', 7, '#dc3545'),
('Ignored', 'Ticket has been deliberately set aside', 8, '#6c757d');

-- Create ticket_priorities table
CREATE TABLE ticket_priorities (
    priority_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    display_order INT NOT NULL DEFAULT 0,
    color VARCHAR(20) NOT NULL, -- Store color for styling
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Insert new priority values as requested
INSERT INTO ticket_priorities (name, description, display_order, color) VALUES
('1 - Critical', 'Must be addressed immediately', 1, '#dc3545'),
('2 - Important', 'High priority issue that should be addressed soon', 2, '#fd7e14'),
('3 - Nice to have', 'Would improve the product but not urgent', 3, '#28a745'),
('4 - Feature request', 'New feature that would add value', 4, '#17a2b8'),
('5 - Cosmetic', 'Visual or non-functional improvement', 5, '#6c757d'),
('6 - Not set', 'Priority has not been determined', 6, '#6c757d');

-- Modify tickets table by removing ENUM and adding foreign keys
-- First create new columns
ALTER TABLE tickets ADD COLUMN status_id INT AFTER url;
ALTER TABLE tickets ADD COLUMN priority_id INT AFTER status_id;

-- Create foreign key relationships
ALTER TABLE tickets 
    ADD CONSTRAINT fk_ticket_status 
    FOREIGN KEY (status_id) REFERENCES ticket_statuses(status_id);

ALTER TABLE tickets 
    ADD CONSTRAINT fk_ticket_priority 
    FOREIGN KEY (priority_id) REFERENCES ticket_priorities(priority_id);

-- Create indexes for performance
CREATE INDEX idx_tickets_status ON tickets(status);
CREATE INDEX idx_tickets_priority ON tickets(priority);
CREATE INDEX idx_tickets_assigned_to ON tickets(assigned_to);
CREATE INDEX idx_activity_log_target ON activity_log(target_type, target_id);