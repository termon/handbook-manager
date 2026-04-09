# Hierarchical Help Navigation

The help system now provides an intuitive hierarchical navigation system! Instead of showing all pages at once, users can browse through directories just like a file explorer.

## How It Works

### Root Level 
(`/help`) Shows only:
- Files in the root help directory (e.g., `index.md`, `faq.md`)
- Directory folders (e.g., `getting-started/`, `api/`)

### Directory Level 
(`/help?page=getting-started`) Shows only:
- Files within that directory
- Subdirectories within that directory
- Back navigation to parent level

### File Level 
(`/help?page=getting-started/installation`)  Shows:
- The actual markdown content
- Table of contents (if enabled)
- Sidebar navigation (if enabled)

## URL Examples

### Navigation Flow
- `/help` → Root level (shows folders and root files)
- `/help?page=getting-started` → Browse getting-started folder
- `/help?page=getting-started/installation` → Read installation guide
- `/help?page=api` → Browse API documentation folder
- `/help?page=api/authentication` → Read authentication docs

## Visual Features

### 🗂️ Folder Icons
- Directories show with blue folder icons
- Files show with green document icons
- Clear visual distinction between browsable folders and readable content

### 🔙 Back Navigation
- Automatic "Back to..." links when browsing subdirectories
- Maintains navigation context
- Breadcrumb-style navigation

### 📝 Smart Descriptions
- Automatically extracts descriptions from markdown content
- For directories: uses index.md first paragraph if available
- For files: uses content after the first heading
- Falls back to sensible defaults

## File Structure Support
```
resources/views/help/
├── index.md                    # Root level file
├── faq.md                      # Root level file
├── getting-started/            # Folder (browsable)
│   ├── index.md               # Optional: folder overview
│   ├── installation.md        # File in folder
│   ├── configuration.md       # File in folder
│   └── troubleshooting.md     # File in folder
├── api/                        # Folder (browsable)
│   ├── index.md               # Optional: folder overview
│   ├── authentication.md      # File in folder
│   └── endpoints.md           # File in folder
└── guides/                     # Folder (browsable)
    ├── user-guide.md          # File in folder
    └── admin-guide.md         # File in folder
```

## Features

### 🗂️ Automatic Categorization
Pages are automatically grouped by their parent directory:
- **Getting Started** section with installation, configuration, etc.
- **API** section with authentication, endpoints, etc.
- **Guides** section with user and admin guides

### 🍞 Smart Breadcrumbs
Nested pages show hierarchical breadcrumbs with working links:
- `Home → Help → Getting Started → Installation`
- `Home → Help → API → Authentication`
- Only creates clickable links for pages that actually exist

### 📁 Directory Index Files
- Each directory can have an `index.md` file for overview content
- Breadcrumb links automatically detect and link to directory index files
- Fallback behavior prevents 404 errors when clicking directory breadcrumbs

### 🔍 Recursive Discovery
The component automatically scans up to 3 levels deep and discovers all markdown files.

### 🔒 Security
- Controller sanitizes each path segment individually
- Prevents directory traversal attacks (../, ./, etc.)
- Maintains clean URLs with slugified segments

### 📱 Enhanced UI
- Categories shown as section headers
- Depth indicators for nested content
- Improved descriptions based on path context
- Responsive grid layout maintained

## Usage Examples

### Simple nested page:
```blade
<x-help page="getting-started/installation" />
```

### Show specific category:
```blade
<x-help page="api/authentication" />
```

### Auto-discovery with categorization:
```blade
<x-help />
```

The system maintains backward compatibility with flat structures while adding powerful organization capabilities!
