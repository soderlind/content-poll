# AI Provider Integration Guide

The Content Vote plugin supports multiple AI providers for generating intelligent vote suggestions. This document describes each provider and how to configure them.

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

### 8. PocketFlow Mode (Multi-Step, OpenAI/Azure)
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
| OpenAI + PocketFlow mode | $$ | Excellent | Medium | External | Topic-aware, robust polls |

*Infrastructure costs only

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

### Security Notes
- API keys are stored in WordPress options (encrypted by WordPress)
- Never expose API keys in frontend code
- All API calls use `wp_remote_post()` for WordPress best practices
- Ollama requires local network access (ensure firewall rules)

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

## Future Enhancements
- Support for custom system prompts
- Temperature/creativity controls
- Caching suggestions to reduce API costs
- Batch suggestion generation
- Provider-specific optimizations
