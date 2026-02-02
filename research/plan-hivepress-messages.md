# HivePress Messages Plugin - Feature Analysis

**Plugin Name:** HivePress Messages
**Version:** 1.4.0
**Author:** HivePress
**License:** GPLv3
**Requirements:** WordPress 5.0+, PHP 7.4+

---

## Core Features

### Private Messaging System
- **Message Model**: Extends WordPress comments system with custom 'hp_message' comment type
- **Storage**: Database storage as comments (optional email-only mode)
- Send messages between users
- Read/unread message tracking
- Sender and recipient information storage
- Message timestamp tracking
- Optional context linking to listings, bookings, orders, or vendors

## Administration Settings

### Sending Section
- File Attachments - Allow/disable file attachments to messages
- Allowed File Types - Configurable MIME types for attachments
- Refresh Interval - Configurable auto-refresh rate (default 60 seconds, minimum 5 seconds)

### Moderation Section
- Message Monitoring - Allow administrators to monitor user conversations
- Blocked Keywords - Filter messages containing specified keywords (case-insensitive regex)

### Storage Section
- Message Storage - Enable database storage vs email-only mode
- Message Deletion - Allow users to delete their sent messages
- Storage Period - Auto-delete old messages after specified days

## REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/messages` | Send a message |
| DELETE | `/messages/{message_id}` | Delete a message |
| GET | `/messages/read` | Mark messages as read |
| GET | `/messages/{message_id}` | Get message details |

All endpoints require authentication.

## Frontend Pages & Routes

- **Messages Thread Page** (`/messages`) - List all active conversations/threads
- **Messages View Page** (`/messages/{user_id}/?{recipient_id}?`) - Single conversation view
- **Monitoring**: Admins can view `messages/{sender_id}/{recipient_id}` for monitoring

## Block Components

### Message Blocks
- `messages_block` - Displays messages in thread or view mode
  - Thread Mode: Table format showing conversation list
  - View Mode: Grid format with real-time refresh for recipients
- `message_send_form_block` - Form to compose and send messages
- `message_view_block` - Individual message display with metadata
- `message_thread_block` - Table row format for thread list

### Template Blocks
- `Messages_View_Page` - Conversation page with messages and send form
- `Messages_Thread_Page` - All conversations listing
- `Message_View_Block` - Individual message layout with header, content, sender, date, attachment
- `Message_Thread_Block` - Thread list row format

## Forms

### Message Send Form
- Fields: message text, recipient (hidden), listing (hidden)
- No CAPTCHA
- Sends to API endpoint: `/messages`
- Success message: "Your message has been sent."

### Message Delete Form
- Confirmation form for message deletion
- Method: DELETE
- Auto-redirects after deletion

## Email Notifications

**Email Type:** `Message_Send`

**Available Tokens:**
- `user_name` - Recipient name
- `message_url` - Link to view message
- `message_text` - Message content
- `message` - Full message object
- `sender` - Sender user object
- `recipient` - Recipient user object

**Default Subject:** "Message Received" or "New reply to [listing name]"

## Integration Points

### Template Integrations
- **Listing Views**: Message send button/modal on listing blocks and pages
- **Vendor Views**: Message send button/modal on vendor profile blocks and pages
- **User Profiles**: Message send button/modal on user profile blocks and pages
- **Marketplace Orders** (if Marketplace plugin active): Message send on order page
- **Bookings** (if Bookings plugin active): Message send on booking blocks and pages

### Context-Aware Messaging
- Messages can be linked to listings
- Messages can be linked to bookings
- Messages can be linked to marketplace orders
- Messages can be linked to vendor profiles
- Automatic recipient detection based on context

## Message Model Fields

| Field | Description |
|-------|-------------|
| `text` | Message content (max 2048 chars, required) |
| `sent_date` | Timestamp when sent |
| `sender` | Sender user ID (required) |
| `sender__display_name` | Sender name (required) |
| `sender__email` | Sender email (required) |
| `recipient` | Recipient user ID (required) |
| `read` | Read status flag (0 or 1) |
| `listing` | Optional linked listing ID |
| `attachment` | Optional file attachment (when enabled) |

## Frontend Features

### Message Display
- Sender information with timestamp
- Message text content
- Read/unread status indicators
- Attachment display (when present)
- Delete button for message owners (if enabled)
- Admin monitoring capabilities

### Message Threading
- Automatic thread grouping by sender-recipient pairs
- Latest message preview in thread list
- Unread message count in account menu
- Auto-refresh for new messages (configurable interval)

### Conversation Management
- View all conversations from account dashboard
- Click to open individual conversations
- Send reply within conversation view
- Track message read status

## Security Features

- **Keyword Filtering**: Messages blocked if containing specified keywords
- **User Validation**: Prevents users from messaging themselves
- **Permission Checks**:
  - Only logged-in users can send messages
  - Users can only delete their own messages (unless admin)
  - Admins can monitor conversations if enabled
  - Recipient validation to ensure user exists and is published
- **Listing Validation**: Linked listings must be published
- **Attachment Validation**: File types restricted to admin-configured MIME types

## Cache System

- User-level caching for:
  - Message drafts (for attachments)
  - Thread IDs
  - Unread message counts
  - Message lists (for large conversations)
- Cache invalidation on message create/update/delete
- Separate cache for monitoring admins

## Message Lifecycle Features

- **Draft Creation**: Auto-created draft message for attachment handling
- **Auto-Deletion**: Old messages automatically deleted after storage period
- **User Deletion**: All messages from/to deleted user are removed
- **Approval Status**: Messages have approval status tracking

## Technical Implementation

### Database Schema
Uses WordPress comments table (`wp_comments`) with comment type `hp_message`:
- `comment_content` → message text
- `comment_author` → sender name
- `comment_author_email` → sender email
- `user_id` → sender ID
- `comment_karma` → recipient ID
- `comment_approved` → read status
- `comment_post_ID` → listing ID
- `comment_date` → sent date

### Architecture
- Model-View-Controller pattern
- RESTful API endpoints
- Component-based UI using HivePress blocks system
- Event-driven hooks for cache management
- Template inheritance system

### Dependencies
- Requires core HivePress plugin
- Optional integrations: Marketplace, Bookings plugins

### Storage Options
- **Database Mode**: Store messages in DB, send notification emails with link
- **Email Mode**: No database storage, send full message via email with reply-to header
