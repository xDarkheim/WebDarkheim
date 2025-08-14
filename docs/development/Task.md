## Complete AI Instructions for Content Management System Redesign for Development Studio Website

### Project Context
Development studio website on PHP/Composer with existing content management system that needs to be reoriented from user-generated content to administrative content, while adding extended client profiles and optional ability to add projects to portfolio.

### Current Project Structure
- MVC architecture (Controllers, Models, Views)
- Routing system through config/routes_config.php
- Middleware for authorization in src/Application/Middleware/
- API endpoints in page/api/
- Moderation system (database/add_moderation_system.sql)
- Draft system (database/add_draft_system.sql)
- Email notifications through PHPMailer
- Backup system (scripts/backup.php)
- Themes in themes/default/

### PHASE 1: Role and Permission System

#### Files to create/modify:
1. `src/Domain/Models/Role.php` - new model
2. `src/Domain/Models/User.php` - add role field
3. `src/Domain/Models/Permission.php` - new model
4. `database/migration_roles_permissions.sql` - new migration

#### Database Structure:
-- Tables to create:
roles (id, name, description)
permissions (id, name, resource, action)
role_permissions (role_id, permission_id)
user_roles (user_id, role_id)

-- Modify existing table:
ALTER TABLE users ADD COLUMN role ENUM('admin', 'employee', 'client', 'guest') DEFAULT 'client';

### PHASE 2: Middleware and Access Restriction

#### Create in src/Application/Middleware/:
1. `AdminOnlyMiddleware.php` - admin only
2. `RoleMiddleware.php` - role checking
3. `ClientAreaMiddleware.php` - client area protection

#### Modify:
- `config/routes_config.php` - apply middleware to routes
- Controllers in src/Application/Controllers/ - add role checks

### PHASE 3: Extended Client Profile

#### New models in src/Domain/Models/:
1. `ClientProfile.php` - extended profile
2. `ClientProject.php` - client projects
3. `ProjectModeration.php` - project moderation

#### ClientProfile fields:
- company_name
- position
- bio
- skills (JSON)
- portfolio_visibility (public/private)
- allow_contact
- social_links (JSON)
- website
- location

#### New database tables:
-- client_profiles: extended profile data
-- client_portfolio: client projects (optional)
-- client_skills: client skills/technologies
-- client_social_links: client social media links
-- project_views: project view statistics

### PHASE 4: Client Portfolio System

#### Create structure:
page/user/portfolio/
├── add_project.php - add project form
├── edit_project.php - edit project
├── my_projects.php - client's projects list
├── project_stats.php - view statistics
└── project_settings.php - visibility settings

page/public/client/
├── profile.php - public client profile
└── portfolio.php - public portfolio

#### Modify existing files:
- `page/public/projects.php` - add filter "Studio Projects" / "Community Projects"
- `page/user/dashboard.php` - transform to client portal

### PHASE 5: Content System Redesign

#### Remove/rename:
- page/user/content/ → remove article creation functionality
- Remove content creation forms from public area

#### Modify:
- `database/add_moderation_system.sql` - adapt for client project and comment moderation
- `database/add_draft_system.sql` - use only for admins and client projects

### PHASE 6: Comment System

#### Create:
1. `src/Domain/Models/Comment.php` - comment model
2. `src/Application/Controllers/CommentController.php` - controller
3. `src/Application/Services/CommentService.php` - business logic
4. `database/create_comments_table.sql` - comments table

#### API endpoints in page/api/:
comments/
├── create.php
├── update.php
├── delete.php
├── moderate.php
└── get_thread.php

### PHASE 7: Client Portal
#### Transform page/user/:
dashboard.php - client homepage
projects/ - studio projects for client
├── index.php - project list
├── details.php - project details
└── timeline.php - project timeline
tickets/ - support system
├── index.php - ticket list
├── create.php - create ticket
└── view.php - view ticket
invoices/ - finances
├── index.php - invoice list
└── download.php - download PDF
documents/ - documentation
meetings/ - meeting scheduling

### PHASE 8: API Modifications

#### Modify existing:
- `page/api/filter_articles.php` - read-only for all
- Remove create/edit methods for regular users

#### Create new in page/api/:
portfolio/
├── create_project.php
├── update_project.php
├── upload_images.php
├── get_client_projects.php
└── toggle_visibility.php

client/
├── profile_update.php
├── skills_update.php
└── social_links.php

notifications/
├── get_unread.php
├── mark_read.php
└── preferences.php

### PHASE 9: Navigation and UI Update

#### Modify resources/views/_main_navigation.php:
- Remove for clients: "Create Article", "My Publications"
- Add for clients: "My Profile", "Portfolio", "Projects", "Support"
- Add for all: "Community" (client projects)

#### Create new views:
resources/views/
├── client/
│   ├── profile_form.php
│   ├── project_form.php
│   └── portfolio_grid.php
├── moderation/
│   ├── project_review.php
│   └── comment_review.php
└── public/
├── client_profile.php
└── community_projects.php

### PHASE 10: Email Templates and Notifications

#### Redesign in resources/views/emails/:
- Remove user article publication templates
- Create new:
    - project_status_update.php - project status change
    - portfolio_moderation.php - portfolio moderation result
    - new_comment_notification.php - new comment
    - ticket_response.php - ticket response

### PHASE 11: Services and Business Logic

#### Create in src/Application/Services/:
1. `ClientProfileService.php` - profile management
2. `PortfolioService.php` - client portfolio management
3. `ProjectModerationService.php` - project moderation
4. `NotificationService.php` - notification system
5. `TicketService.php` - support system
6. `InvoiceService.php` - invoice generation

### PHASE 12: Admin Panel

#### Create in page/admin/:
1. `moderate_projects.php` - client project moderation
2. `moderate_comments.php` - comment moderation
3. `client_management.php` - client management
4. `portfolio_settings.php` - portfolio system settings

### Implementation Timeline

1. Week 1-2: Role and middleware system
2. Week 3-4: Extended client profiles
3. Week 5-6: Portfolio system with moderation
4. Week 7: Redesign existing content
5. Week 8-9: Comment system
6. Week 10-11: Client portal
7. Week 12: Final integration and testing

### Critical Checks

- [ ] Regular users CANNOT create news/articles
- [ ] Admins have full content management access
- [ ] Clients can add projects to portfolio (with moderation)
- [ ] Public project page correctly separates types
- [ ] Moderation system works for comments and projects
- [ ] Email notifications are sent correctly
- [ ] Client profiles have privacy settings
- [ ] API endpoints are protected with appropriate rights

### What to Keep Unchanged
- Backup system (scripts/backup.php)
- Monitoring (page/admin/system_monitor.php)
- SEO functionality
- MVC structure
- Routing system
- Themes (themes/default/)
- Composer dependencies

### Additional Notes
- Client portfolio is an optional feature (can be disabled in settings)
- All client projects require pre-moderation before publication
- Maintain clear separation between official studio projects and client portfolios
- Use existing infrastructure as efficiently as possible
- Apply existing category system for tagging client projects
