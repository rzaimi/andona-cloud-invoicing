# Deployment Guide

## Hosting Environment Setup

### Node.js Version Requirements

This project uses **Vite 7** and **laravel-vite-plugin 2.0**, which require:
- Node.js **20.19.0+** or **22.12.0+**
- Node.js **21.x is NOT supported**

### Installation on Hosting

#### Option 1: Use Supported Node.js Version (Recommended)

If your hosting supports `nodenv` or `nvm`:

```bash
# Using nodenv
nodenv install 20.19.0
nodenv local 20.19.0

# Initialize nodenv in current shell
eval "$(nodenv init -)"
export PATH="$HOME/.nodenv/bin:$PATH"

# Verify Node version
node -v  # Should show v20.19.0 or v22.12.0+

# Install dependencies
npm ci

# Build assets
npm run build
```

#### Option 2: Handle Node.js 21.x (Workaround)

If you're stuck with Node.js 21.x, you'll need to use npm flags to bypass engine checks:

1. **Fix nodenv PATH issue** (for @tailwindcss/oxide installation):
   ```bash
   eval "$(nodenv init -)"
   export PATH="$HOME/.nodenv/bin:$PATH"
   export PATH="$HOME/.nodenv/shims:$PATH"
   
   # Or create a wrapper script
   export PATH=$(nodenv root)/shims:$PATH
   ```

2. **Run installation with workaround**:
   ```bash
   # Set PATH before npm install
   export PATH="$(nodenv root)/shims:$PATH"
   npm install
   ```

3. **Alternative: Use npm install script**:
   ```bash
   npm install --ignore-engines --legacy-peer-deps
   ```

### Troubleshooting

#### Error: `nodenv: node: command not found`

This occurs when npm scripts try to run `node` but it's not in PATH. Solutions:

1. **Set PATH in your shell profile**:
   ```bash
   echo 'eval "$(nodenv init -)"' >> ~/.bashrc
   echo 'export PATH="$HOME/.nodenv/bin:$PATH"' >> ~/.bashrc
   source ~/.bashrc
   ```

2. **Use absolute path in npm scripts** (if you know nodenv location):
   ```bash
   # Find node path
   nodenv which node
   # Use this path in your deployment script
   ```

3. **Create a symlink** (if permissions allow):
   ```bash
   ln -s $(nodenv which node) /usr/local/bin/node
   ```

#### Error: `EBADENGINE Unsupported engine`

Use the `--ignore-engines` flag to bypass engine version checks:

```bash
npm install --ignore-engines
```

Note: This is a workaround. The packages may still work, but it's recommended to use a supported Node.js version (20.19.0+ or 22.12.0+).

### Production Build

After successful installation:

```bash
# Build for production
npm run build

# Verify build output
ls -la public/build/
```

### CI/CD Integration

For automated deployments, ensure your deployment script:

1. Sets up the correct Node.js version
2. Initializes nodenv/nvm properly
3. Runs `npm ci` (uses exact versions from package-lock.json)
4. Runs `npm run build`

Example deployment script:
```bash
#!/bin/bash
set -e

# Setup Node.js
eval "$(nodenv init -)"
nodenv local 20.19.0 || nvm use 20.19.0

# Install dependencies
npm ci

# Build assets
npm run build

# Deploy (your deployment commands here)
```

