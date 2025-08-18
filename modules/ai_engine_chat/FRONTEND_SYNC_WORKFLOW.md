# Frontend Sync Workflow Documentation

This repository manages the synchronization of Microsoft's `sample-app-aoai-chatGPT` frontend with Yale's custom modifications for the Drupal AI Engine module.

## Branch Strategy

### 🔄 `upstream-sync`
- **Purpose**: Pure Microsoft code only
- **Source**: Always synced from Microsoft's repository
- **Never modify**: This branch should only receive updates from Microsoft
- **Usage**: Base for merging Microsoft updates

### 🏗️ `yale-development` 
- **Purpose**: Your Drupal-specific modifications
- **Source**: Based on Microsoft code + your customizations
- **Daily work**: This is where you make all your changes
- **Usage**: Active development branch

### 🚀 `main`
- **Purpose**: Deployment-ready merged code
- **Source**: Combination of `upstream-sync` + `yale-development`
- **Usage**: Code that gets copied to the Drupal module

## Initial Setup

### 1. Repository Setup
```bash
# Clone your fork
git clone https://github.com/yalesites-org/askyale-aoai-chat-app.git
cd askyale-aoai-chat-app

# Add Microsoft as upstream remote
git remote add upstream https://github.com/microsoft/sample-app-aoai-chatGPT.git
git fetch upstream

# Create branches
git checkout -b upstream-sync upstream/main
git push origin upstream-sync:upstream-sync
git branch --set-upstream-to=origin/upstream-sync upstream-sync

git checkout -b yale-development upstream/main
git push origin yale-development:yale-development
git branch --set-upstream-to=origin/yale-development yale-development

git checkout main
```

### 2. Verify Setup
```bash
# Check remotes
git remote -v
# Should show both 'origin' (your fork) and 'upstream' (Microsoft)

# Check branch tracking
git branch -vv
# All branches should show proper tracking
```

## Daily Development Workflow

### Working on Your Changes
```bash
# Switch to development branch
git checkout yale-development

# Make your modifications
# - Edit frontend files
# - Add Drupal-specific configurations
# - Update API endpoints
# - Customize styling

# Commit your work
git add .
git commit -m "Add Yale branding and Drupal API integration"
git push
```

### Viewing Your Changes
```bash
# See what files you've modified compared to Microsoft's code
git checkout yale-development
git diff upstream-sync

# See commit history of your changes
git log upstream-sync..yale-development --oneline
```

## Microsoft Update Workflow

### Weekly/Monthly Sync Process

#### Step 1: Update Microsoft's Code
```bash
# Get latest from Microsoft
git checkout upstream-sync
git pull upstream main
git push origin upstream-sync
```

#### Step 2: Merge Microsoft Updates
```bash
# Merge Microsoft's updates into your development branch
git checkout yale-development
git merge upstream-sync

# If there are conflicts, resolve them:
# 1. Open conflicted files in your editor
# 2. Look for conflict markers:
#    <<<<<<< HEAD
#    Your changes
#    =======
#    Microsoft's changes
#    >>>>>>> upstream-sync
# 3. Edit to keep the code you want
# 4. Remove conflict markers
# 5. Test the changes
```

#### Step 3: Test and Commit
```bash
# Install dependencies and test
npm install
npm run build
npm run dev  # Test in browser

# If everything works, commit the merge
git add .
git commit -m "Merge Microsoft updates - resolved conflicts in ChatInterface"
git push
```

#### Step 4: Update Main Branch
```bash
# Create deployment-ready version
git checkout main
git merge upstream-sync        # Get Microsoft's latest
git merge yale-development     # Add your modifications

# Final testing
npm install
npm run build

# Tag the release (optional but recommended)
git tag v1.2.3
git push origin main --tags
```

## Deployment to Drupal Module

### Manual Deployment
```bash
# Copy frontend to your Drupal module
cd /path/to/your/drupal-repo

# Remove old frontend
rm -rf ai_engine/modules/ai_engine_chat/react

# Copy new frontend
cp -r /path/to/askyale-aoai-chat-app/frontend/* ai_engine/modules/ai_engine_chat/react/

# Clean up git artifacts
find ai_engine/modules/ai_engine_chat/react -name ".git*" -delete

# Commit in Drupal repo
git add ai_engine/modules/ai_engine_chat/react
git commit -m "Update frontend to latest version with Microsoft updates"
```

### Automated Deployment Script
Create `deploy-frontend.sh` in your Drupal repository:

```bash
#!/bin/bash
FORK_REPO="../askyale-aoai-chat-app"
DRUPAL_MODULE="ai_engine/modules/ai_engine_chat"

echo "Deploying frontend from fork..."

# Ensure fork is on main branch
cd "$FORK_REPO"
git checkout main
git pull

# Get current version info
COMMIT_SHA=$(git rev-parse --short HEAD)
COMMIT_MSG=$(git log -1 --pretty=format:"%s")

# Copy to Drupal module
cd - 
rm -rf "$DRUPAL_MODULE/react"
mkdir -p "$DRUPAL_MODULE/react"
cp -r "$FORK_REPO/frontend"/* "$DRUPAL_MODULE/react/"

# Clean up
find "$DRUPAL_MODULE/react" -name ".git*" -delete

echo "Frontend deployed successfully!"
echo "Version: $COMMIT_SHA"
echo "Last commit: $COMMIT_MSG"
echo ""
echo "Next steps:"
echo "1. Test the integration"
echo "2. git add $DRUPAL_MODULE/react"
echo "3. git commit -m 'Update frontend to $COMMIT_SHA'"
```

Make it executable:
```bash
chmod +x deploy-frontend.sh
```

## Common Commands

### Check Status
```bash
# See which branch you're on
git branch

# See what files have changed
git status

# See recent commits
git log --oneline -5

# Compare branches
git diff upstream-sync yale-development
```

### Branch Navigation
```bash
# Switch branches
git checkout yale-development
git checkout upstream-sync
git checkout main

# Create new feature branch
git checkout yale-development
git checkout -b feature/new-chat-widget
```

### Emergency Fixes
```bash
# If you accidentally commit to upstream-sync
git checkout upstream-sync
git reset --hard origin/upstream-sync

# If you need to undo changes in yale-development
git checkout yale-development
git reset --hard HEAD~1  # Undo last commit
# or
git reset --hard origin/yale-development  # Reset to remote
```

## Best Practices

### ✅ Do:
- Always work in `yale-development` branch
- Regularly sync with Microsoft updates (weekly/monthly)
- Test thoroughly after merging Microsoft updates
- Use clear commit messages describing your changes
- Tag releases in main branch for easy rollback

### ❌ Don't:
- Never commit directly to `upstream-sync`
- Don't ignore conflicts - resolve them properly
- Don't deploy without testing first
- Don't modify files in `ai_engine/modules/ai_engine_chat/react` directly (always copy from fork)

## Troubleshooting

### Merge Conflicts
If you get conflicts when merging Microsoft updates:

1. **Don't panic** - conflicts are normal when Microsoft changes the same files you've modified
2. **Open the conflicted files** in VS Code or your preferred editor
3. **Look for conflict markers** (`<<<<<<<`, `=======`, `>>>>>>>`)
4. **Decide which code to keep** - usually you want to combine both changes
5. **Remove the conflict markers** 
6. **Test the resolution**
7. **Commit the merge**

### Reset Branch to Clean State
```bash
# Reset yale-development to match upstream-sync (lose your changes!)
git checkout yale-development
git reset --hard upstream-sync

# Reset to last working state
git reset --hard origin/yale-development
```

### View Changes History
```bash
# See what changed in last Microsoft update
git checkout upstream-sync
git log --oneline -10

# See your changes vs Microsoft's
git checkout yale-development
git log upstream-sync..HEAD --oneline

# See file changes
git diff upstream-sync -- frontend/src/components/ChatInterface.tsx
```

## Repository Structure

### Fork Repository Structure
```
askyale-aoai-chat-app/
├── frontend/                 # React frontend (Microsoft's + your changes)
│   ├── src/
│   │   ├── components/
│   │   ├── api/
│   │   └── ...
│   ├── package.json
│   └── vite.config.ts
├── backend/                  # Microsoft's backend (reference only)
├── deploy-frontend.sh        # Deployment script
└── README.md                 # This documentation
```

### Drupal Module Structure
```
ai_engine/                    # Main Drupal module
├── ai_engine.info.yml
├── src/                      # PHP classes
├── modules/                  # Submodules
│   └── ai_engine_chat/       # Chat functionality submodule
│       ├── ai_engine_chat.info.yml
│       ├── src/              # PHP classes
│       ├── js/               # Drupal-specific JS integration
│       └── react/            # Copied from fork's frontend/* 
│           ├── src/
│           ├── package.json
│           └── vite.config.ts
└── libraries.yml
```

## Questions?

If you run into issues:

1. Check the git status: `git status`
2. Check which branch you're on: `git branch`
3. Look at recent commits: `git log --oneline -5`
4. If stuck, create an issue in this repository with the error message

Remember: The goal is to keep Microsoft's updates flowing in while preserving your Drupal-specific customizations!