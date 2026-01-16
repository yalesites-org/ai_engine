# AI Feed Module

## Overview

The AI Engine suite comprises modules that empower Drupal websites with AI capabilities, facilitating the transformation of Drupal's data model into a Language Model (LLM). It facilitates the creation of embeddings from Drupal content and metadata, enabling efficient content management and transformation into a vector database. Additionally, it offers a question-and-answer chat service for public views.

## Configuration

### AI Engine Chat

The AI Engine Chat module provides a configurable floating chat button and Q&A interface.

#### Chat Button Icon Configuration

You can customize the icon displayed on the floating chat button to better align with your site's branding and user experience.

**Configuration path:** `/admin/config/ai-engine/chat-admin`

**Available icon options:**
- Chat (default) - General chat/discussion icon
- Sparkles - Emphasizes AI-powered assistant
- Message - Direct messaging style
- Robot - Emphasizes AI/automation
- Question Circle - Help/FAQ assistant style

**To change the chat button icon:**
1. Navigate to Configuration → AI Engine → Chat Admin Settings
2. Under "Floating button icon", select your preferred icon from the dropdown
3. Click "Save configuration"
4. The icon change will take effect immediately on your site

**Note:** This feature requires Font Awesome to be loaded on your site for the icons to display correctly.
