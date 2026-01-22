# ML Category AI Description

**🤖 AI-Powered Category Content Generator for PrestaShop**

Automatically generate SEO-optimized category descriptions, meta titles, meta descriptions, keywords, and friendly URLs using artificial intelligence (OpenAI or Azure OpenAI).

---

## 📦 Installation

1. **Upload the module**
   - Go to **Modules > Module Manager** in your PrestaShop back office
   - Click **Upload a module** (top right)
   - Select the `mlcategoryaidescription.zip` file
   
2. **Install the module**
   - Find "ML Category AI Description" in the module list
   - Click **Install**

3. **Configure your API key**
   - After installation, click **Configure**
   - Enter your OpenAI API key (see Configuration section below)

---

## ⚙️ Configuration

Access module configuration: **Modules > Module Manager > ML Category AI Description > Configure**

### 🔑 API Settings

#### API Provider

| Option | Description |
|--------|-------------|
| **OpenAI** | Standard OpenAI API (recommended for most users) |
| **Azure OpenAI** | Microsoft Azure hosted OpenAI (for enterprise users) |
| **Custom** | Self-hosted or alternative OpenAI-compatible APIs |

#### API Key

Your OpenAI API key for authentication.

**How to get your API key:**
1. Go to [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)
2. Sign in or create an account
3. Click **"Create new secret key"**
4. Copy the key (starts with `sk-...`)
5. Paste it in the API Key field

⚠️ **Important**: Keep your API key secret. Never share it publicly.

#### API Model

Select which AI model to use:

| Model | Speed | Quality | Cost |
|-------|-------|---------|------|
| **gpt-4o-mini** | ⚡ Fast | ✅ Good | 💰 Low |
| **gpt-4o** | Medium | ✅✅ Excellent | 💰💰 Medium |
| **gpt-4** | Slow | ✅✅✅ Best | 💰💰💰 High |

💡 **Recommendation**: Start with **gpt-4o-mini** for testing. It's fast and affordable.

#### API Endpoint

- **OpenAI**: Leave default (`https://api.openai.com/v1`)
- **Azure**: Enter your Azure resource URL (e.g., `https://your-resource.openai.azure.com`)

---

### 📝 Generation Settings

#### Batch Size

How many items to process in each batch (1-20).

- **Recommended**: 5-10
- Lower values = more stable, but slower
- Higher values = faster, but may timeout

#### Write Mode

| Mode | Behavior |
|------|----------|
| **Overwrite All** | Replace existing content with new AI-generated content |
| **Fill Missing Only** | Only generate content for empty fields (recommended) |

💡 **Recommendation**: Use **Fill Missing Only** to avoid overwriting manually written content.

#### Max Tokens

Maximum length of AI response (100-4000).

| Field Type | Recommended Tokens |
|------------|-------------------|
| Description | 800-1500 |
| Meta Title | 100-150 |
| Meta Description | 200-300 |
| Meta Keywords | 150-200 |

#### Temperature

Controls creativity of AI responses (0.0-1.5).

| Value | Style |
|-------|-------|
| **0.3** | Conservative, predictable |
| **0.7** | Balanced (recommended) |
| **1.0+** | Creative, varied |

#### Request Delay

Seconds to wait between API requests (0.0-5.0).

- **Recommended**: 0.5-1.0 seconds
- Helps prevent API rate limiting
- Set higher if you get rate limit errors

#### Enable Parallel Requests

Process multiple requests simultaneously for faster generation.

- **Yes**: Faster processing (3-5x speed improvement)
- **No**: Sequential processing (more stable)

---

### ⏰ Background Processing (CRON)

Enable CRON to run generation in the background without keeping your browser open.

#### Enable CRON

Toggle to enable background processing mode.

#### CRON URL

After enabling, you'll see a URL like:
```
https://yourshop.com/module/mlcategoryaidescription/cron?token=abc123...
```

**Setup options:**

1. **Server CRON job** (recommended):
   ```bash
   */5 * * * * curl -s "YOUR_CRON_URL" > /dev/null
   ```
   This runs every 5 minutes.

2. **External CRON service**: Use services like cron-job.org or EasyCron with your CRON URL.

---

## 🚀 Usage Guide

### Step 1: Configure API

1. Go to module configuration
2. Enter your OpenAI API key
3. Select model (gpt-4o-mini recommended)
4. Save settings

### Step 2: Customize Prompts (Optional)

1. Scroll to **Prompt Templates** section
2. Click on a field type (e.g., "Description")
3. Edit the prompt template for each language
4. Use placeholders like `{category_name}`, `{parent_name}`, `{products_list}`
5. Save templates

### Step 3: Generate Content

1. **Select Categories**: Choose which categories to process
   - Use "Select All" for entire catalog
   - Or select specific categories from the tree

2. **Select Languages**: Choose target languages
   - Select all languages for multilingual content
   - Or select specific languages

3. **Select Fields**: Choose what content to generate
   - ✅ Description (main category text)
   - ✅ Meta Title (SEO page title)
   - ✅ Meta Description (SEO snippet)
   - ✅ Meta Keywords (SEO keywords)
   - ✅ Friendly URL (URL slug)

4. **Choose Write Mode**:
   - **Fill Missing Only**: Safe option, keeps existing content
   - **Overwrite All**: Replace all selected fields

5. **Click "Start Generation"**

### Step 4: Monitor Progress

- Progress bar shows completion percentage
- View status in **Job Status** section
- Jobs can be paused and resumed
- Failed items are logged for review

---

## 📋 Features

### ✅ Multi-Language Support
Generate content for all your shop languages in one batch. The AI respects each language's prompt template.

### ✅ Customizable Prompts
Edit prompt templates for each field type and language. Include context about your business, tone preferences, and specific instructions.

### ✅ Available Placeholders

Use these in your prompt templates:

| Placeholder | Description | Example |
|-------------|-------------|---------|
| `{category_name}` | Category name | Men's Shoes |
| `{category_breadcrumb}` | Full category path (all parents) | Clothing > Footwear > Men's Shoes |
| `{category_description}` | Current category description | Browse our collection... |
| `{category_url}` | Full URL to category page | https://myshop.com/en/mens-shoes |
| `{parent_category_name}` | Direct parent category name | Footwear |
| `{site_name}` | Your shop name | MyShop |
| `{site_description}` | Shop meta description | Your online store... |
| `{shop_url}` | Shop base URL | https://myshop.com/ |
| `{product_list}` | Up to 50 product names | Product A, Product B... |
| `{product_count}` | Number of products in category | 42 |
| `{first_products:N}` | First N products from category | {first_products:10} |
| `{random_products:N}` | N random products from category | {random_products:5} |
| `{language_code}` | Target language ISO code | en, fr, de |
| `{language_name}` | Target language full name | English, Français |

💡 **Tip**: Use `{category_breadcrumb}` to give the AI full context about category hierarchy!

### ✅ Resume Support
Long-running jobs can be paused and resumed. If your browser closes, start again and click "Resume" to continue from where you left off.

### ✅ Fill Missing Mode
Only generate content for empty fields. Existing manually-written content is preserved.

### ✅ Job Dashboard
Monitor all generation jobs with:
- Progress percentage
- Items processed/failed
- Start and completion times
- Ability to cancel stuck jobs

### ✅ Performance Statistics
View generation metrics:
- Total runs and items generated
- Token usage
- Average execution time
- API response times

---

## 🔧 Troubleshooting

### "API Key Invalid" Error

- Check that your API key is correct (starts with `sk-...`)
- Ensure your OpenAI account has payment method configured
- Check API key hasn't expired or been revoked

### "Rate Limit" Errors

- Increase **Request Delay** to 1-2 seconds
- Disable **Parallel Requests**
- Use a smaller **Batch Size**

### Generation Stops/Freezes

- Check your browser didn't go to sleep
- Enable **CRON mode** for long jobs
- Reduce batch size to prevent timeouts

### Empty or Poor Quality Results

- Edit your prompt templates with more context
- Include examples of desired output style
- Increase **Max Tokens** for longer content

### "Stuck" Jobs

- Go to Job Status section
- Click "Delete" on stuck jobs
- Start a new generation

---

## 📊 Recommended Settings for Beginners

| Setting | Value |
|---------|-------|
| API Provider | OpenAI |
| Model | gpt-4o-mini |
| Batch Size | 5 |
| Write Mode | Fill Missing Only |
| Max Tokens | 1000 |
| Temperature | 0.7 |
| Request Delay | 0.5 |
| Parallel Requests | Yes |

---

## 💰 API Costs

OpenAI charges based on tokens (roughly 4 characters = 1 token).

**Approximate costs with gpt-4o-mini:**
- ~$0.01-0.02 per category (all fields)
- 100 categories ≈ $1-2
- 1000 categories ≈ $10-20

Actual costs depend on prompt length and response size.

---

## 🖥️ Requirements

- PrestaShop 1.7.x, 8.x, or 9.x
- PHP 7.2 or higher (PHP 8.0+ recommended)
- cURL PHP extension enabled
- OpenAI API key or Azure OpenAI access

---

## 🆘 Support

Need help? Contact us:

- **PrestaShop Addons**: Through your order page
- **Website**: [2win.agency](https://2win.agency)
- **Email**: Support through PrestaShop Addons messaging

---

## 📝 License

This module is licensed for use on **1 website per license purchase**.

For additional websites, please purchase additional licenses.

© 2010-2026 2win.agency - All rights reserved.
