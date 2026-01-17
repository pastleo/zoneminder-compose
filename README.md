# ZoneMinder Container with Modern Dark Theme

This repository contains Docker Compose configuration for running ZoneMinder using [zoneminder-base container](https://github.com/zoneminder-containers/zoneminder-base) [builds](https://github.com/zoneminder-containers/zoneminder-base/pkgs/container/zoneminder-base)

## Features

- **ZoneMinder CCTV System**: Full-featured video surveillance system
- **MariaDB Database**: Persistent storage for events and configuration
- **OAuth2 Proxy**: Google OAuth authentication (optional)

## Quick Start

1. **Copy configuration files**:
   ```bash
   cp .env.example .env
   cp compose.example.yml compose.yml
   ```

2. **Edit `.env` file**:
   - Set database passwords
   - Configure timezone (TZ)

3. **Edit `compose.yml` file**:
   - Update the ZoneMinder image tag to your desired version
   - Configure port mappings if needed

4. **Start the services**:
   ```bash
   docker compose up -d
   ```
