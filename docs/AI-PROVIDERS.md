# AI Provider Integration Guide

The Content Vote plugin supports multiple AI providers for generating intelligent vote suggestions. This document describes each provider and how to configure them.

> **See also:** [Provider Architecture](provider-architecture.md) for detailed implementation diagrams and code examples.

## Available Providers

### 1. Heuristic AI (Default)
- **API Key Required**: No
- **Cost**: Free
- **Description**: Built-in keyword-based suggestion system that analyzes content frequency
- **Best For**: Testing, small sites, or when AI APIs aren't needed
- **Configuration**: None required - works out of the box

### 2. OpenAI
- **API Key Required**: Yes
- **Cost**: Pay per use
- **Models**: `gpt-3.5-turbo`, `gpt-4`, `gpt-4-turbo`
- **Description**: Industry-leading GPT models for high-quality suggestions
- **Best For**: Production sites requiring sophisticated AI
- **API Key**: Get from [OpenAI Platform](https://platform.openai.com/api-keys)
- **Configuration**:
  - Provider: OpenAI
  - OpenAI Type: OpenAI
  - API Key: Your OpenAI API key
  - Model: `gpt-3.5-turbo` (recommended for cost)

### 3. Azure OpenAI
- **API Key Required**: Yes
- **Cost**: Pay per use (enterprise pricing)
- **Models**: Same as OpenAI
- **Description**: OpenAI models hosted on Microsoft Azure
- **Best For**: Enterprise sites with Azure infrastructure, compliance requirements
- **API Key**: From Azure OpenAI resource
- **Configuration**:
  - Provider: OpenAI
  - OpenAI Type: Azure OpenAI
  - API Key: Your Azure OpenAI API key
  - Deployment Name: Your Azure deployment name
  - Azure Endpoint: `https://YOUR-RESOURCE.openai.azure.com`
  - API Version: `2024-02-15-preview` (or latest)

### 4. Anthropic Claude
- **API Key Required**: Yes
- **Cost**: Pay per use
- **Models**: `claude-3-5-sonnet-20241022`, `claude-3-opus`, `claude-3-sonnet`
- **Description**: Advanced AI with strong reasoning and long context support
- **Best For**: High-quality content analysis, longer articles
- **API Key**: Get from [Anthropic Console](https://console.anthropic.com/)
- **Configuration**:
  - Provider: Anthropic Claude
  - API Key: Your Anthropic API key
  - Model: `claude-3-5-sonnet-20241022` (recommended)

### 5. Google Gemini
- **API Key Required**: Yes
- **Cost**: Free tier available, then pay per use
- **Models**: `gemini-1.5-flash`, `gemini-1.5-pro`
- **Description**: Google's multimodal AI with generous free tier
- **Best For**: Budget-conscious sites, testing AI features
- **API Key**: Get from [Google AI Studio](https://aistudio.google.com/app/apikey)
- **Configuration**:
  - Provider: Google Gemini
  - API Key: Your Google AI API key
  - Model: `gemini-1.5-flash` (free tier)

### 6. Ollama (Self-Hosted)
- **API Key Required**: No
- **Cost**: Free (self-hosted infrastructure costs only)
- **Models**: `llama3.2`, `mistral`, `phi`, `gemma`, etc.
- **Description**: Run open-source AI models locally on your server
- **Best For**: Privacy-sensitive sites, no external API dependencies
- **Setup**: Install [Ollama](https://ollama.ai/) and pull models
- **Configuration**:
  - Provider: Ollama (Self-Hosted)
  - Endpoint: `http://localhost:11434` (default)
  - Model: `llama3.2` or any installed model

### 7. Grok (xAI)
- **API Key Required**: Yes
- **Cost**: Pay per use (xAI pricing)
- **Models**: `grok-2` (default) plus future variants as released
- **Description**: Real-time reasoning model from xAI focused on concise contextual understanding.
- **Best For**: Fast, succinct poll suggestion generation leveraging emerging reasoning capabilities.
- **API Key**: Obtain from your xAI account dashboard.
- **Configuration**:
  - Provider: Grok (xAI)
  - API Key: Your xAI API key
  - Model: `grok-2` (default) or updated released model

### 8. Exo (Local Cluster)
- **API Key Required**: No
- **Cost**: Free (self-hosted hardware costs only)
- **Models**: Any models supported by your Exo cluster (e.g., `llama-3.2-3b`, `deepseek-r1`)
- **Description**: Exo is a distributed inference cluster that allows running large AI models across multiple consumer devices. It exposes an OpenAI-compatible API locally.
- **Best For**: Running large models locally across multiple machines, full privacy, no cloud dependencies.
- **Setup**: Install [Exo](https://github.com/exo-explore/exo) and start the cluster.
- **Configuration**:
  - Provider: Exo (Local Cluster)
  - Endpoint: `http://localhost:8000` (or your Exo cluster URL)
  - Model: Select from running models via the "Refresh Models" button
- **Features**:
  - ✅ Dynamic model list fetched from your cluster
  - ✅ Health check indicator shows connection status
  - ✅ No API key required (local network only)
  - ✅ Streaming SSE response handling

### 9. PocketFlow Mode (Multi-Step, OpenAI/Azure)
- **API Key Required**: Yes (uses your OpenAI/Azure credentials)
- **Cost**: Same as your configured OpenAI/Azure usage
- **Models**: Any chat-capable OpenAI or Azure OpenAI deployment you have configured
- **Description**: Optional internal multi-step flow inspired by PocketFlow that first extracts topics from the content and then generates a poll question and options, with an extra validation pass to normalize the JSON.
- **Best For**: Posts where you want more deliberate, topic-aware polls while keeping your existing OpenAI/Azure setup.
- **Configuration**:
  - AI Provider: OpenAI
  - OpenAI Type: OpenAI or Azure OpenAI
  - API Key: Your OpenAI or Azure OpenAI API key
  - Model / Deployment: Same as for the regular OpenAI provider
  - Azure Endpoint/API Version: Required only when using Azure OpenAI
  - PocketFlow multi-step mode: Enabled (checkbox in AI Settings)


## Provider Comparison

| Provider / Mode | Cost | Quality | Speed | Privacy | Best Use Case |
|-----------------|------|---------|-------|---------|---------------|
| Heuristic | Free | Basic | Fast | Full | Testing/Simple sites |
| OpenAI | $$ | Excellent | Fast | External | Production quality |
| Azure OpenAI | $$$ | Excellent | Fast | Enterprise | Compliance required |
| Anthropic | $$ | Excellent | Fast | External | Long content |
| Gemini | $/Free | Good | Fast | External | Budget-conscious |
| Ollama | Free* | Good | Medium | Full | Privacy-first |
| Grok (xAI) | $$ | Good/Emerging | Fast | External | Real-time reasoning |
| Exo | Free* | Excellent | Medium | Full | Distributed local LLM |
| OpenAI + PocketFlow mode | $$ | Excellent | Medium | External | Topic-aware, robust polls |

*Infrastructure/hardware costs only

## Implementation Details

### How It Works
1. When creating a vote block, click "Suggest from Content"
2. Plugin analyzes the post content (first 1000 characters)
3. Sends request to configured AI provider
4. AI returns JSON with question and 4-6 options
5. If AI fails, falls back to heuristic method

### API Endpoints

- **OpenAI**: `https://api.openai.com/v1/chat/completions`
- **Azure OpenAI**: `{endpoint}/openai/deployments/{deployment}/chat/completions?api-version={version}`
- **Anthropic**: `https://api.anthropic.com/v1/messages`
- **Gemini**: `https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={key}`
- **Ollama**: `{endpoint}/api/generate`
- **Grok (xAI)**: `https://api.x.ai/v1/chat/completions`
- **Exo**: `{endpoint}/v1/chat/completions` (OpenAI-compatible)

### Security Notes
- API keys are stored in WordPress options (encrypted by WordPress)
- Never expose API keys in frontend code
- All API calls use `wp_remote_post()` for WordPress best practices
- Ollama requires local network access (ensure firewall rules)

## Environment Variables & Constants

For deployment flexibility (Docker, CI/CD, wp-config.php), you can configure AI settings via environment variables or PHP constants instead of the database. Values are resolved in this priority order:

1. **PHP Constant** (highest priority) - defined in `wp-config.php`
2. **Environment Variable** - set in server environment
3. **Database Option** - configured via Settings page
4. **Default Value** - built-in fallback

### Available Configuration Variables

| Setting | Environment Variable | PHP Constant | Default |
|---------|---------------------|--------------|---------|
| AI Provider | `CONTENT_POLL_AI_PROVIDER` | `CONTENT_POLL_AI_PROVIDER` | `heuristic` |
| OpenAI Type | `CONTENT_POLL_OPENAI_TYPE` | `CONTENT_POLL_OPENAI_TYPE` | `openai` |
| OpenAI API Key | `CONTENT_POLL_OPENAI_KEY` | `CONTENT_POLL_OPENAI_KEY` | (empty) |
| OpenAI Model | `CONTENT_POLL_OPENAI_MODEL` | `CONTENT_POLL_OPENAI_MODEL` | `gpt-3.5-turbo` |
| Azure Endpoint | `CONTENT_POLL_AZURE_ENDPOINT` | `CONTENT_POLL_AZURE_ENDPOINT` | (empty) |
| Azure API Version | `CONTENT_POLL_AZURE_API_VERSION` | `CONTENT_POLL_AZURE_API_VERSION` | `2024-02-15-preview` |
| Anthropic API Key | `CONTENT_POLL_ANTHROPIC_KEY` | `CONTENT_POLL_ANTHROPIC_KEY` | (empty) |
| Anthropic Model | `CONTENT_POLL_ANTHROPIC_MODEL` | `CONTENT_POLL_ANTHROPIC_MODEL` | `claude-3-5-sonnet-20241022` |
| Gemini API Key | `CONTENT_POLL_GEMINI_KEY` | `CONTENT_POLL_GEMINI_KEY` | (empty) |
| Gemini Model | `CONTENT_POLL_GEMINI_MODEL` | `CONTENT_POLL_GEMINI_MODEL` | `gemini-1.5-flash` |
| Ollama Endpoint | `CONTENT_POLL_OLLAMA_ENDPOINT` | `CONTENT_POLL_OLLAMA_ENDPOINT` | `http://localhost:11434` |
| Ollama Model | `CONTENT_POLL_OLLAMA_MODEL` | `CONTENT_POLL_OLLAMA_MODEL` | `llama3.2` |
| Grok API Key | `CONTENT_POLL_GROK_KEY` | `CONTENT_POLL_GROK_KEY` | (empty) |
| Grok Model | `CONTENT_POLL_GROK_MODEL` | `CONTENT_POLL_GROK_MODEL` | `grok-2` |
| Exo Endpoint | `CONTENT_POLL_EXO_ENDPOINT` | `CONTENT_POLL_EXO_ENDPOINT` | (empty) |
| Exo Model | `CONTENT_POLL_EXO_MODEL` | `CONTENT_POLL_EXO_MODEL` | (empty) |

### Example: wp-config.php Constants (OpenAI)

```php
// In wp-config.php - before "That's all, stop editing!"
define( 'CONTENT_POLL_AI_PROVIDER', 'openai' );
define( 'CONTENT_POLL_OPENAI_TYPE', 'openai' );
define( 'CONTENT_POLL_OPENAI_KEY', 'sk-your-api-key-here' );
define( 'CONTENT_POLL_OPENAI_MODEL', 'gpt-4' );
```

### Example: wp-config.php Constants (Azure OpenAI)

```php
// In wp-config.php - before "That's all, stop editing!"
define( 'CONTENT_POLL_AI_PROVIDER', 'openai' );
define( 'CONTENT_POLL_OPENAI_TYPE', 'azure' );
define( 'CONTENT_POLL_OPENAI_KEY', 'your-azure-api-key-here' );
define( 'CONTENT_POLL_OPENAI_MODEL', 'your-deployment-name' );
define( 'CONTENT_POLL_AZURE_ENDPOINT', 'https://your-resource.openai.azure.com' );
define( 'CONTENT_POLL_AZURE_API_VERSION', '2024-02-15-preview' );
```

### Example: Environment Variables (.env or server config)

```bash
# For Docker, .env files, or server environment
export CONTENT_POLL_AI_PROVIDER=anthropic
export CONTENT_POLL_ANTHROPIC_KEY=sk-ant-your-key-here
export CONTENT_POLL_ANTHROPIC_MODEL=claude-3-5-sonnet-20241022
```

### Example: Docker Compose

```yaml
services:
  wordpress:
    environment:
      CONTENT_POLL_AI_PROVIDER: openai
      CONTENT_POLL_OPENAI_KEY: ${OPENAI_API_KEY}
      CONTENT_POLL_OPENAI_MODEL: gpt-4
```

### Admin UI Behavior

When a setting is defined via environment variable or constant:
- The field appears as **read-only** in the Settings page
- A "(Set via wp-config.php constant)" or "(Set via environment variable)" indicator is shown
- The value cannot be changed from the admin UI
- API keys are masked for security

This allows developers to lock down production configurations while still allowing settings UI for testing or development environments.

## Troubleshooting

### AI Not Working
1. Check API key is entered correctly
2. Verify model name matches provider's available models
3. Check WordPress error logs for API response errors
4. Test API key with provider's official tools
5. Falls back to Heuristic if AI fails

### Ollama Connection Issues
1. Ensure Ollama is running: `ollama serve`
2. Verify endpoint URL is correct
3. Pull model first: `ollama pull llama3.2`
4. Check firewall allows local connections
5. Test endpoint: `curl http://localhost:11434/api/version`

### Exo Connection Issues
1. Ensure Exo cluster is running: check with `curl http://localhost:8000/v1/models`
2. Verify endpoint URL matches your Exo configuration (default port is 8000 or 52415)
3. Use the "Check Connection" button in settings to verify connectivity
4. Click "Refresh Models" to see available models from your cluster
5. Ensure at least one model is loaded in your Exo cluster
6. Check firewall allows local connections on the configured port

## Future Enhancements
- Support for custom system prompts
- Temperature/creativity controls
- Caching suggestions to reduce API costs
- Batch suggestion generation
- Provider-specific optimizations
